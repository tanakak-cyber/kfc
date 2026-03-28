<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Season;
use App\Models\SeasonPlayerPoint;
use App\Support\SeasonCatchFeed;
use App\Support\SeasonPlayerCatchStats;
use App\Support\SeasonPlayerStandings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $currentSeason = Season::query()->where('is_current', true)->first();

        $seasonStandings = collect();
        $seasonCatchStats = collect();
        $seasonCatchFeed = collect();
        if ($currentSeason) {
            $seasonCatchFeed = SeasonCatchFeed::approvedForSeason($currentSeason->id);
            $seasonCatchStats = SeasonPlayerCatchStats::statsByPlayerId($currentSeason->id);
            $seasonStandings = SeasonPlayerPoint::query()
                ->where('season_id', $currentSeason->id)
                ->with('player')
                ->get();
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
            'seasonCatchFeed',
            'recentMatches',
            'pastSeasons'
        ));
    }
}
