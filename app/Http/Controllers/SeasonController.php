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
            ->with(['matchResults' => fn ($q) => $q->orderBy('rank')->with('team')])
            ->orderByDesc('held_at')
            ->get();

        $standings = SeasonPlayerPoint::query()
            ->where('season_id', $season->id)
            ->with('player')
            ->orderByDesc('total_points')
            ->orderBy('player_id')
            ->get();
        $standings = SeasonPlayerStandings::attachDisplayRanks($standings);

        $seasonCatchStats = SeasonPlayerCatchStats::statsByPlayerId($season->id);

        $seasonCatchFeed = SeasonCatchFeed::approvedForSeason($season->id);

        return view('seasons.show', compact('season', 'matches', 'standings', 'seasonCatchStats', 'seasonCatchFeed'));
    }
}
