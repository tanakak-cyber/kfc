<?php

namespace App\Support;

use App\Models\FishCatch;
use App\Models\GameMatch;
use Illuminate\Support\Collection;

/**
 * 承認済み釣果を match_results の順位に沿ってセクション分けする（試合ページ・シーズンフィード共通）。
 */
final class CatchSectionsByRank
{
    /**
     * @return list<array{fallback_flat: bool, rank: int|null, heading: string|null, catches: Collection<int, FishCatch>}>
     */
    public static function build(GameMatch $gameMatch, Collection $catches): array
    {
        $results = $gameMatch->matchResults->sortBy('rank')->values();
        if ($results->isEmpty()) {
            return [[
                'fallback_flat' => true,
                'rank' => null,
                'heading' => null,
                'catches' => $catches,
            ]];
        }

        $sections = [];

        if ($gameMatch->isTeamMatch()) {
            $byTeam = $catches->groupBy('team_id');
            $seenTeamIds = [];

            foreach ($results as $r) {
                if ($r->team_id === null) {
                    continue;
                }
                $seenTeamIds[$r->team_id] = true;
                $team = $r->team;
                $heading = $team !== null
                    ? $r->rank.'位 '.$team->name
                    : $r->rank.'位';

                $sections[] = [
                    'fallback_flat' => false,
                    'rank' => $r->rank,
                    'heading' => $heading,
                    'catches' => $byTeam->get($r->team_id, collect()),
                ];
            }

            $orphans = $catches->filter(function (FishCatch $c) use ($seenTeamIds): bool {
                return $c->team_id !== null && ! isset($seenTeamIds[$c->team_id]);
            });
            if ($orphans->isNotEmpty()) {
                $sections[] = [
                    'fallback_flat' => false,
                    'rank' => null,
                    'heading' => '順位表にないチームの釣果',
                    'catches' => $orphans->values(),
                ];
            }
        } else {
            $byPlayer = $catches->groupBy('player_id');
            $seenPlayerIds = [];

            foreach ($results as $r) {
                if ($r->player_id === null) {
                    continue;
                }
                $seenPlayerIds[$r->player_id] = true;
                $player = $r->player;
                $heading = $player !== null
                    ? $r->rank.'位 '.$player->displayLabel()
                    : $r->rank.'位';

                $sections[] = [
                    'fallback_flat' => false,
                    'rank' => $r->rank,
                    'heading' => $heading,
                    'catches' => $byPlayer->get($r->player_id, collect()),
                ];
            }

            $orphans = $catches->filter(function (FishCatch $c) use ($seenPlayerIds): bool {
                return ! isset($seenPlayerIds[$c->player_id]);
            });
            if ($orphans->isNotEmpty()) {
                $sections[] = [
                    'fallback_flat' => false,
                    'rank' => null,
                    'heading' => '順位表にない選手の釣果',
                    'catches' => $orphans->values(),
                ];
            }
        }

        if ($sections === []) {
            return [[
                'fallback_flat' => true,
                'rank' => null,
                'heading' => null,
                'catches' => $catches,
            ]];
        }

        return $sections;
    }
}
