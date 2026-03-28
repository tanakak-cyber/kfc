<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchResult;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonPlayerPoint;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class MatchResultSyncService
{
    public function __construct(
        private RankingService $ranking
    ) {}

    public function syncMatch(GameMatch $match, bool $approvedOnly = true): void
    {
        $teams = $match->teams()->get();
        $rows = $this->ranking->rankTeams($teams, $approvedOnly);

        DB::transaction(function () use ($match, $rows): void {
            $teamIds = collect($rows)->pluck('team_id')->all();

            MatchResult::query()
                ->where('match_id', $match->id)
                ->whereNotIn('team_id', $teamIds ?: [0])
                ->delete();

            foreach ($rows as $row) {
                MatchResult::query()->updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'team_id' => $row['team_id'],
                    ],
                    [
                        'rank' => $row['rank'],
                        'total_weight' => $row['total_weight'],
                        'big_fish_weight' => $row['big_fish_weight'],
                        'points' => $row['points'],
                    ]
                );
            }
        });
    }

    public function rebuildSeasonPlayerPoints(Season $season): void
    {
        DB::transaction(function () use ($season): void {
            SeasonPlayerPoint::query()->where('season_id', $season->id)->delete();

            $totals = [];

            $finalizedMatchIds = GameMatch::query()
                ->where('season_id', $season->id)
                ->where('is_finalized', true)
                ->pluck('id');

            if ($finalizedMatchIds->isEmpty()) {
                foreach (Player::query()->pluck('id') as $playerId) {
                    SeasonPlayerPoint::query()->create([
                        'season_id' => $season->id,
                        'player_id' => $playerId,
                        'total_points' => 0,
                    ]);
                }

                return;
            }

            $results = MatchResult::query()
                ->whereIn('match_id', $finalizedMatchIds)
                ->get(['team_id', 'points']);

            foreach ($results as $mr) {
                $playerIds = TeamMember::query()
                    ->where('team_id', $mr->team_id)
                    ->pluck('player_id');
                foreach ($playerIds as $pid) {
                    $totals[$pid] = ($totals[$pid] ?? 0) + $mr->points;
                }
            }

            foreach (Player::query()->pluck('id') as $playerId) {
                SeasonPlayerPoint::query()->create([
                    'season_id' => $season->id,
                    'player_id' => $playerId,
                    'total_points' => (int) ($totals[$playerId] ?? 0),
                ]);
            }
        });
    }
}
