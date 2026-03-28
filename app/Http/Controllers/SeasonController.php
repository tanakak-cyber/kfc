<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Season;
use App\Models\SeasonPlayerPoint;
use App\Support\SeasonCatchFeed;
use App\Support\SeasonPlayerCatchStats;
use App\Support\SeasonPlayerStandings;
use Illuminate\View\View;

class SeasonController extends Controller
{
    public function index(): View
    {
        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return view('seasons.index', compact('seasons'));
    }

    public function show(Season $season): View
    {
        $matches = GameMatch::query()
            ->where('season_id', $season->id)
            ->with(['matchResults' => fn ($q) => $q->orderBy('rank')->with(['team', 'player'])])
            ->orderByDesc('start_datetime')
            ->get();

        $seasonCatchStats = SeasonPlayerCatchStats::statsByPlayerId($season->id);

        $standings = SeasonPlayerPoint::query()
            ->where('season_id', $season->id)
            ->with('player')
            ->get();
        $standings = SeasonPlayerStandings::orderByPointsCatchCountMaxWeight($standings, $seasonCatchStats);
        $standings = SeasonPlayerStandings::attachDisplayRanks($standings);

        $seasonCatchFeed = SeasonCatchFeed::approvedForSeason($season->id);

        return view('seasons.show', compact('season', 'matches', 'standings', 'seasonCatchStats', 'seasonCatchFeed'));
    }
}
