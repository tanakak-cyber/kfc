<?php

namespace App\Support;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
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
            ->orderByDesc('matches.start_datetime')
            ->orderByDesc('catches.id')
            ->with(['player', 'team', 'images', 'gameMatch'])
            ->get();
    }

    /**
     * トップ・シーズン詳細用: 試合ごとに見出しを分け、その中で順位（match_results）順に釣果を並べる。
     *
     * @return list<array{match: GameMatch, catchSections: list<array<string, mixed>>}>
     */
    public static function groupedByMatchAndRank(int $seasonId): array
    {
        $catches = FishCatch::query()
            ->select('catches.*')
            ->join('matches', 'matches.id', '=', 'catches.match_id')
            ->where('matches.season_id', $seasonId)
            ->where('catches.approval_status', CatchApprovalStatus::Approved)
            ->orderByDesc('matches.start_datetime')
            ->orderByDesc('catches.id')
            ->with(['player', 'team', 'images'])
            ->get();

        if ($catches->isEmpty()) {
            return [];
        }

        $byMatchId = $catches->groupBy('match_id');

        $matches = GameMatch::query()
            ->whereIn('id', $byMatchId->keys())
            ->where('season_id', $seasonId)
            ->with([
                'matchResults' => fn ($q) => $q->orderBy('rank')->with(['team', 'player']),
            ])
            ->orderByDesc('start_datetime')
            ->get();

        $out = [];
        foreach ($matches as $match) {
            $slice = $byMatchId->get($match->id, collect());
            $out[] = [
                'match' => $match,
                'catchSections' => CatchSectionsByRank::build($match, $slice),
            ];
        }

        return $out;
    }
}
