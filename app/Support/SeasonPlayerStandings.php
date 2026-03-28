<?php

namespace App\Support;

use App\Models\SeasonPlayerPoint;
use Illuminate\Support\Collection;

final class SeasonPlayerStandings
{
    /**
     * ポイント降順 → 釣果数降順 → 最大重量（g）降順 → player_id 昇順。
     *
     * @param  Collection<int, SeasonPlayerPoint>  $standings
     * @param  Collection<int, array{catch_count: int, max_length_cm: string|null, max_weight_g: string|null}>  $catchStatsByPlayerId
     * @return Collection<int, SeasonPlayerPoint>
     */
    public static function orderByPointsCatchCountMaxWeight(Collection $standings, Collection $catchStatsByPlayerId): Collection
    {
        return $standings->sort(function (SeasonPlayerPoint $a, SeasonPlayerPoint $b) use ($catchStatsByPlayerId): int {
            $pc = $b->total_points <=> $a->total_points;
            if ($pc !== 0) {
                return $pc;
            }

            $ca = (int) data_get($catchStatsByPlayerId->get($a->player_id), 'catch_count', 0);
            $cb = (int) data_get($catchStatsByPlayerId->get($b->player_id), 'catch_count', 0);
            $cc = $cb <=> $ca;
            if ($cc !== 0) {
                return $cc;
            }

            $wa = data_get($catchStatsByPlayerId->get($a->player_id), 'max_weight_g');
            $wb = data_get($catchStatsByPlayerId->get($b->player_id), 'max_weight_g');
            $wa = $wa !== null && $wa !== '' ? (float) $wa : 0.0;
            $wb = $wb !== null && $wb !== '' ? (float) $wb : 0.0;
            $wc = $wb <=> $wa;
            if ($wc !== 0) {
                return $wc;
            }

            return $a->player_id <=> $b->player_id;
        })->values();
    }

    /**
     * @param  Collection<int, SeasonPlayerPoint>  $standings
     * @return Collection<int, SeasonPlayerPoint>
     */
    public static function attachDisplayRanks(Collection $standings): Collection
    {
        $list = $standings->values();
        $displayRank = 1;
        foreach ($list as $i => $row) {
            if ($i > 0 && $row->total_points < $list[$i - 1]->total_points) {
                $displayRank = $i + 1;
            }
            $row->display_rank = $displayRank;
        }

        return $list;
    }
}
