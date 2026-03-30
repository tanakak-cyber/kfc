<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Enums\CatchScoringBasis;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Services\MatchResultSyncService;
use Illuminate\View\View;

class PublicMatchController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    public function show(GameMatch $gameMatch): View
    {
        $gameMatch->load([
            'season',
            'teams.players',
            'matchParticipants.player',
            'matchResults' => fn ($q) => $q->orderBy('rank')->with(['team.players', 'player']),
            'matchPlayerBonusPoints.player',
        ]);

        $catchesQuery = FishCatch::query()
            ->where('match_id', $gameMatch->id)
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->with(['player', 'images', 'team']);

        if ($gameMatch->resolvedCatchScoringBasis() === CatchScoringBasis::Length) {
            $catchesQuery->orderByRaw('COALESCE(length_cm, 0) DESC');
        } else {
            $catchesQuery->orderByDesc('weight_g');
        }

        $catches = $catchesQuery->get();

        $playerBonusTotals = $gameMatch->matchPlayerBonusPoints
            ->groupBy('player_id')
            ->map(fn ($rows) => (int) $rows->sum('points'));

        $teamMatchPlayerBreakdown = $this->matchResults->teamMatchPlayerBreakdownRows($gameMatch, $playerBonusTotals);

        return view('matches.show', compact(
            'gameMatch',
            'catches',
            'playerBonusTotals',
            'teamMatchPlayerBreakdown'
        ));
    }
}
