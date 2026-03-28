<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Season;
use App\Models\SeasonPlayerPoint;
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
        if ($currentSeason) {
            $seasonStandings = SeasonPlayerPoint::query()
                ->where('season_id', $currentSeason->id)
                ->with('player')
                ->orderByDesc('total_points')
                ->orderBy('player_id')
                ->get();
            $seasonStandings = SeasonPlayerStandings::attachDisplayRanks($seasonStandings);
            $seasonCatchStats = SeasonPlayerCatchStats::statsByPlayerId($currentSeason->id);
        }

        $recentMatches = GameMatch::query()
            ->with(['season', 'matchResults' => fn ($q) => $q->orderBy('rank')->with('team')])
            ->when($currentSeason, fn ($q) => $q->where('season_id', $currentSeason->id))
            ->orderByDesc('held_at')
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
            'recentMatches',
            'pastSeasons'
        ));
    }
}
