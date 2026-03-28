<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatchApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatchModerationController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
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

    private function matchOrAbort(FishCatch $catch): GameMatch
    {
        $catch->loadMissing('gameMatch');

        return $catch->gameMatch;
    }
}
