<?php

namespace App\Support;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use Illuminate\Support\Collection;

final class SeasonPlayerCatchStats
{
    /**
     * シーズンに紐づく試合の承認済み釣果を、プレイヤーごとに集計する。
     *
     * @return Collection<int, array{catch_count: int, max_length_cm: string|null, max_weight_g: string|null}>
     */
    public static function statsByPlayerId(int $seasonId): Collection
    {
        return FishCatch::query()
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->whereHas('gameMatch', fn ($q) => $q->where('season_id', $seasonId))
            ->groupBy('player_id')
            ->selectRaw('player_id, COUNT(*) as season_catch_count, MAX(length_cm) as season_max_length_cm, MAX(weight_g) as season_max_weight_g')
            ->get()
            ->keyBy(fn ($row) => (int) $row->player_id)
            ->map(fn ($row) => [
                'catch_count' => (int) $row->season_catch_count,
                'max_length_cm' => $row->season_max_length_cm !== null ? (string) $row->season_max_length_cm : null,
                'max_weight_g' => $row->season_max_weight_g !== null ? (string) $row->season_max_weight_g : null,
            ]);
    }
}
