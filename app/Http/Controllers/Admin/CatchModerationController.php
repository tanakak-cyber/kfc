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
use Illuminate\Support\Facades\DB;
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

    /**
     * 一覧に表示中の未承認釣果をまとめて承認する（ページネーションの現在ページ分）。
     *
     * @param  array<int, int|string>  $request->catch_ids
     */
    public function approveBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'catch_ids' => ['required', 'array', 'min:1'],
            'catch_ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['catch_ids'])));

        $catches = FishCatch::query()
            ->whereIn('id', $ids)
            ->where('approval_status', CatchApprovalStatus::Pending)
            ->with('gameMatch')
            ->orderBy('id')
            ->get();

        if ($catches->isEmpty()) {
            return back()->withErrors(['catch' => '承認対象の未承認釣果がありません。']);
        }

        $approved = 0;
        $skippedFinalized = 0;
        $matchIdsToSync = [];

        DB::transaction(function () use ($catches, &$approved, &$skippedFinalized, &$matchIdsToSync): void {
            foreach ($catches as $fishCatch) {
                $match = $fishCatch->gameMatch;
                if ($match->is_finalized) {
                    $skippedFinalized++;

                    continue;
                }
                $fishCatch->update(['approval_status' => CatchApprovalStatus::Approved]);
                $approved++;
                $matchIdsToSync[$match->id] = true;
            }
        });

        foreach (array_keys($matchIdsToSync) as $matchId) {
            $match = GameMatch::query()->find($matchId);
            if ($match !== null) {
                $this->matchResults->syncMatch($match, true);
            }
        }

        if ($approved === 0) {
            return back()->withErrors(['catch' => '確定済み試合の釣果のみで、承認できませんでした。']);
        }

        $msg = $approved === 1
            ? '1件の釣果を承認しました。'
            : "{$approved}件の釣果を承認しました。";
        if ($skippedFinalized > 0) {
            $msg .= "（確定済み試合の{$skippedFinalized}件はスキップしました）";
        }

        return back()->with('status', $msg);
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

    /**
     * 未承認釣果の画像を、クライアントで累積した 90° 単位の回転を一括で保存する。
     * rotations[catch_image_id] = 時計回りの四半回転数の合計（負も可、サーバ側で mod 4 を適用）。
     */
    public function rotateImagesBatch(Request $request): RedirectResponse
    {
        $rotations = $request->input('rotations', []);
        if (! is_array($rotations) || $rotations === []) {
            return back()->withErrors(['catch' => '回転の変更がありません。']);
        }

        $processed = 0;
        $errors = [];

        foreach ($rotations as $imageId => $netQuarters) {
            $imageId = (int) $imageId;
            $netQuarters = (int) $netQuarters;
            if ($netQuarters === 0) {
                continue;
            }

            $catchImage = CatchImage::query()->find($imageId);
            if (! $catchImage) {
                continue;
            }

            $fishCatch = $catchImage->fishCatch;
            if ($fishCatch->approval_status !== CatchApprovalStatus::Pending) {
                $errors[] = '未承認の釣果のみ向きを変更できます。';

                continue;
            }

            $match = $this->matchOrAbort($fishCatch);
            if ($match->is_finalized) {
                $errors[] = '確定済み試合の釣果は変更できません。';

                continue;
            }

            try {
                $this->imageRotation->rotateQuarterTurns($catchImage->path, $netQuarters);
                $catchImage->touch();
                $processed++;
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($processed === 0) {
            $message = $errors !== []
                ? implode(' ', array_unique($errors))
                : '適用する回転がありません。';

            return back()->withErrors(['catch' => $message]);
        }

        $msg = $processed === 1
            ? '1件の画像の向きを更新しました。'
            : "{$processed}件の画像の向きを更新しました。";

        return back()->with('status', $msg);
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
