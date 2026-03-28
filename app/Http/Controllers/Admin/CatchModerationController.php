<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatchApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\CatchImage;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Services\CatchImageRotationService;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CatchModerationController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults,
        private CatchImageRotationService $imageRotation
    ) {}

    public function index(): View
    {
        $catches = FishCatch::query()
            ->with(['gameMatch.season', 'team', 'player', 'images'])
            ->where('approval_status', CatchApprovalStatus::Pending)
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.catches.index', compact('catches'));
    }

    public function approve(FishCatch $fishCatch): RedirectResponse
    {
        $match = $this->matchOrAbort($fishCatch);
        if ($match->is_finalized) {
            return back()->withErrors(['catch' => '確定済み試合の釣果は変更できません。']);
        }

        $fishCatch->update(['approval_status' => CatchApprovalStatus::Approved]);
        $this->matchResults->syncMatch($match, true);

        return back()->with('status', '釣果を承認しました。');
    }

    public function reject(FishCatch $fishCatch): RedirectResponse
    {
        $match = $this->matchOrAbort($fishCatch);
        if ($match->is_finalized) {
            return back()->withErrors(['catch' => '確定済み試合の釣果は変更できません。']);
        }

        $fishCatch->update(['approval_status' => CatchApprovalStatus::Rejected]);
        $this->matchResults->syncMatch($match, true);

        return back()->with('status', '釣果を却下しました。');
    }

    public function rotateImage(Request $request, FishCatch $fishCatch, CatchImage $catchImage): RedirectResponse
    {
        if ((int) $catchImage->catch_id !== (int) $fishCatch->id) {
            abort(404);
        }

        if ($fishCatch->approval_status !== CatchApprovalStatus::Pending) {
            return back()->withErrors(['catch' => '未承認の釣果のみ向きを変更できます。']);
        }

        $match = $this->matchOrAbort($fishCatch);
        if ($match->is_finalized) {
            return back()->withErrors(['catch' => '確定済み試合の釣果は変更できません。']);
        }

        $validated = $request->validate([
            'direction' => ['required', 'in:cw,ccw'],
        ]);

        $quarters = $validated['direction'] === 'cw' ? 1 : -1;

        try {
            $this->imageRotation->rotateQuarterTurns($catchImage->path, $quarters);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['catch' => $e->getMessage()]);
        }

        $catchImage->touch();

        return back()->with('status', '画像の向きを更新しました。');
    }

    public function updateMeasurements(Request $request, FishCatch $fishCatch): RedirectResponse
    {
        if ($fishCatch->approval_status !== CatchApprovalStatus::Pending) {
            return back()->withErrors(['catch' => '未承認の釣果のみ数値を変更できます。']);
        }

        $match = $this->matchOrAbort($fishCatch);
        if ($match->is_finalized) {
            return back()->withErrors(['catch' => '確定済み試合の釣果は変更できません。']);
        }

        $validator = Validator::make($request->all(), [
            'length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'weight_g' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'length_cm' => '長さ（cm）',
            'weight_g' => '重さ（g）',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'pending_catch_'.$fishCatch->id);
        }

        $validated = $validator->validated();

        $fishCatch->update([
            'length_cm' => $validated['length_cm'],
            'weight_g' => (int) $validated['weight_g'],
        ]);

        return back()->with('status', '長さ・重さを更新しました。');
    }

    private function matchOrAbort(FishCatch $catch): GameMatch
    {
        $catch->loadMissing('gameMatch');

        return $catch->gameMatch;
    }
}
