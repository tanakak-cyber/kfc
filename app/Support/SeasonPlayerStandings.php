<?php

namespace App\Support;

use App\Models\SeasonPlayerPoint;
use Illuminate\Support\Collection;

final class SeasonPlayerStandings
{
    /**
     * 全順位ロジック共通の並び順（順位表・自動チーム作成で統一）。
     *   1. 対象シーズンの合計ポイント 降順
     *   2. シーズン内での最大重量（g）降順
     *   3. シーズン通算釣果数 降順
     *   （最終タイブレークは id 昇順で決定的にする）
     */
    public static function compareStanding(
        int $pointsA,
        float $maxWeightA,
        int $catchCountA,
        int $idA,
        int $pointsB,
        float $maxWeightB,
        int $catchCountB,
        int $idB,
    ): int {
        $c = $pointsB <=> $pointsA;
        if ($c !== 0) {
            return $c;
        }

        $c = $maxWeightB <=> $maxWeightA;
        if ($c !== 0) {
            return $c;
        }

        $c = $catchCountB <=> $catchCountA;
        if ($c !== 0) {
            return $c;
        }

        return $idA <=> $idB;
    }

    /**
     * 合計ポイント降順 → シーズン内最大重量（g）降順 → シーズン通算釣果数降順 → player_id 昇順。
     *
     * @param  Collection<int, SeasonPlayerPoint>  $standings
     * @param  Collection<int, array{catch_count: int, max_length_cm: string|null, max_weight_g: string|null}>  $catchStatsByPlayerId
     * @return Collection<int, SeasonPlayerPoint>
     */
    public static function orderByPointsMaxWeightCatchCount(Collection $standings, Collection $catchStatsByPlayerId): Collection
    {
        return $standings->sort(function (SeasonPlayerPoint $a, SeasonPlayerPoint $b) use ($catchStatsByPlayerId): int {
            $statsA = $catchStatsByPlayerId->get($a->player_id);
            $statsB = $catchStatsByPlayerId->get($b->player_id);

            return self::compareStanding(
                (int) $a->total_points,
                self::maxWeightValue($statsA),
                (int) data_get($statsA, 'catch_count', 0),
                (int) $a->player_id,
                (int) $b->total_points,
                self::maxWeightValue($statsB),
                (int) data_get($statsB, 'catch_count', 0),
                (int) $b->player_id,
            );
        })->values();
    }

    /**
     * 釣果統計の max_weight_g を数値化（未計測・空は 0）。
     *
     * @param  array{catch_count: int, max_length_cm: string|null, max_weight_g: string|null}|null  $stats
     */
    public static function maxWeightValue(?array $stats): float
    {
        $w = data_get($stats, 'max_weight_g');

        return $w !== null && $w !== '' ? (float) $w : 0.0;
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
