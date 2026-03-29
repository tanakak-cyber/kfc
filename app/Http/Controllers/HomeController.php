<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Season;
use App\Services\MatchResultSyncService;
use App\Support\SeasonCatchFeed;
use App\Support\SeasonPlayerCatchStats;
use App\Support\SeasonPlayerParticipationStats;
use App\Support\SeasonPlayerStandings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    public function __invoke(): View
    {
        $currentSeason = Season::query()->where('is_current', true)->first();

        $seasonStandings = collect();
        $seasonCatchStats = collect();
        $seasonParticipationStats = collect();
        $seasonCatchFeed = collect();
        if ($currentSeason) {
            $seasonCatchFeed = SeasonCatchFeed::approvedForSeason($currentSeason->id);
            $seasonCatchStats = SeasonPlayerCatchStats::statsByPlayerId($currentSeason->id);
            $seasonParticipationStats = SeasonPlayerParticipationStats::statsByPlayerId($currentSeason->id);
            $seasonStandings = $this->matchResults->seasonPlayerStandingModels($currentSeason);
            $seasonStandings = SeasonPlayerStandings::orderByPointsCatchCountMaxWeight($seasonStandings, $seasonCatchStats);
            $seasonStandings = SeasonPlayerStandings::attachDisplayRanks($seasonStandings);
        }

        $recentMatches = GameMatch::query()
            ->with(['season', 'matchResults' => fn ($q) => $q->orderBy('rank')->with(['team', 'player'])])
            ->when($currentSeason, fn ($q) => $q->where('season_id', $currentSeason->id))
            ->orderByDesc('start_datetime')
            ->limit(12)
            ->get();

        $pastSeasons = Season::query()
            ->where('is_current', false)
            ->orderByDesc('starts_on')
            ->get();

        return view('home', compact(
            'currentSeason',
            'seasonStandings',
            'seasonCatchStats',
            'seasonParticipationStats',
            'seasonCatchFeed',
            'recentMatches',
            'pastSeasons'
        ));
    }
}
