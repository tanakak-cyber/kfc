<?php

namespace App\Support;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use Illuminate\Support\Collection;

final class SeasonCatchFeed
{
    /**
     * シーズンに属する試合の承認済み釣果を、試合日の新しい順で返す。
     *
     * @return Collection<int, FishCatch>
     */
    public static function approvedForSeason(int $seasonId): Collection
    {
        return FishCatch::query()
            ->select('catches.*')
            ->join('matches', 'matches.id', '=', 'catches.match_id')
            ->where('matches.season_id', $seasonId)
            ->where('catches.approval_status', CatchApprovalStatus::Approved)
            ->orderByDesc('matches.held_at')
            ->orderByDesc('catches.id')
            ->with(['player', 'team', 'images', 'gameMatch'])
            ->get();
    }
}
