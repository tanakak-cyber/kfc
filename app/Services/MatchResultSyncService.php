<?php

namespace App\Services;

use App\Enums\MatchType;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\MatchPlayerBonusPoint;
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
        if ($match->match_type === MatchType::Individual) {
            $this->syncIndividualMatch($match, $approvedOnly);
        } else {
            $this->syncTeamMatch($match, $approvedOnly);
        }
    }

    private function syncTeamMatch(GameMatch $match, bool $approvedOnly): void
    {
        $teams = $match->teams()->get();
        $rows = $this->ranking->rankTeams($teams, $approvedOnly);

        DB::transaction(function () use ($match, $rows): void {
            MatchResult::query()->where('match_id', $match->id)->delete();

            foreach ($rows as $row) {
                MatchResult::query()->create([
                    'match_id' => $match->id,
                    'team_id' => $row['team_id'],
                    'player_id' => null,
                    'rank' => $row['rank'],
                    'total_weight' => $row['total_weight'],
                    'big_fish_weight' => $row['big_fish_weight'],
                    'points' => $row['points'],
                ]);
            }
        });
    }

    private function syncIndividualMatch(GameMatch $match, bool $approvedOnly): void
    {
        $players = MatchParticipant::query()
            ->where('match_id', $match->id)
            ->where('is_present', true)
            ->with('player')
            ->get()
            ->pluck('player')
            ->filter(fn (?Player $p) => $p !== null)
            ->values();

        $rows = $this->ranking->rankPlayers($match, $players, $approvedOnly);

        DB::transaction(function () use ($match, $rows): void {
            MatchResult::query()->where('match_id', $match->id)->delete();

            foreach ($rows as $row) {
                MatchResult::query()->create([
                    'match_id' => $match->id,
                    'team_id' => null,
                    'player_id' => $row['player_id'],
                    'rank' => $row['rank'],
                    'total_weight' => $row['total_weight'],
                    'big_fish_weight' => $row['big_fish_weight'],
                    'points' => $row['points'],
                ]);
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
                ->get(['team_id', 'player_id', 'points']);

            foreach ($results as $mr) {
                if ($mr->player_id !== null) {
                    $pid = (int) $mr->player_id;
                    $totals[$pid] = ($totals[$pid] ?? 0) + (int) $mr->points;

                    continue;
                }

                if ($mr->team_id === null) {
                    continue;
                }

                $playerIds = TeamMember::query()
                    ->where('team_id', $mr->team_id)
                    ->pluck('player_id');
                foreach ($playerIds as $pid) {
                    $totals[(int) $pid] = ($totals[(int) $pid] ?? 0) + (int) $mr->points;
                }
            }

            $bonusRows = MatchPlayerBonusPoint::query()
                ->whereIn('match_id', $finalizedMatchIds)
                ->get(['player_id', 'points']);

            foreach ($bonusRows as $bonus) {
                $pid = (int) $bonus->player_id;
                $totals[$pid] = ($totals[$pid] ?? 0) + (int) $bonus->points;
            }

            foreach (Player::query()->pluck('id') as $playerId) {
                SeasonPlayerPoint::query()->create([
                    'season_id' => $season->id,
                    'player_id' => $playerId,
                    'total_points' => (int) ($totals[(int) $playerId] ?? 0),
                ]);
            }
        });
    }
}
