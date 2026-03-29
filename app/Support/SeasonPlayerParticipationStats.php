<?php

namespace App\Support;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\TeamMember;
use Illuminate\Support\Collection;

final class SeasonPlayerParticipationStats
{
    /**
     * シーズン内の「参加試合数」と「坊主回数」（承認済み釣果が 1 件もない試合の回数）。
     * 開始日時が未来の試合は集計に含めない（未開催分を除外）。
     *
     * @return Collection<int, array{matches_played: int, blank_matches: int}>
     */
    public static function statsByPlayerId(int $seasonId): Collection
    {
        $matchIds = GameMatch::query()
            ->where('season_id', $seasonId)
            ->where('start_datetime', '<=', now())
            ->pluck('id');

        if ($matchIds->isEmpty()) {
            return collect();
        }

        $matchIdList = $matchIds->all();

        /** @var array<int, array<int, true>> $matchesByPlayer */
        $matchesByPlayer = [];

        $add = function (int $playerId, int $matchId) use (&$matchesByPlayer): void {
            $matchesByPlayer[$playerId] ??= [];
            $matchesByPlayer[$playerId][$matchId] = true;
        };

        MatchParticipant::query()
            ->whereIn('match_id', $matchIdList)
            ->where('is_present', true)
            ->get(['match_id', 'player_id'])
            ->each(function (MatchParticipant $row) use ($add): void {
                $add((int) $row->player_id, (int) $row->match_id);
            });

        TeamMember::query()
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->whereIn('teams.match_id', $matchIdList)
            ->select(['team_members.player_id', 'teams.match_id'])
            ->get()
            ->each(function ($row) use ($add): void {
                $add((int) $row->player_id, (int) $row->match_id);
            });

        /** @var array<string, true> $approvedCatchKeys "playerId:matchId" */
        $approvedCatchKeys = [];

        FishCatch::query()
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->whereIn('match_id', $matchIdList)
            ->select(['player_id', 'match_id'])
            ->distinct()
            ->get()
            ->each(function (FishCatch $row) use (&$approvedCatchKeys): void {
                $approvedCatchKeys[(int) $row->player_id.':'.(int) $row->match_id] = true;
            });

        $out = collect();

        foreach ($matchesByPlayer as $playerId => $matchMap) {
            $played = count($matchMap);
            $blank = 0;

            foreach (array_keys($matchMap) as $matchId) {
                $key = $playerId.':'.$matchId;
                if (! isset($approvedCatchKeys[$key])) {
                    $blank++;
                }
            }

            $out[(int) $playerId] = [
                'matches_played' => $played,
                'blank_matches' => $blank,
            ];
        }

        return $out;
    }
}
