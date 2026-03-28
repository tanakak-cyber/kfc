<?php

namespace App\Services;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Collection;

class RankingService
{
    /**
     * 上位3本の合計・最大をグラム単位で返す（match_results と同じ意味）。
     *
     * @return array{total_weight: float, big_fish_weight: float}
     */
    public function teamScore(Team $team, bool $approvedOnly): array
    {
        $query = $team->catches()->orderByDesc('weight_g');

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        $weights = $query->limit(3)->pluck('weight_g')->map(fn ($w) => (float) $w);
        $total = round($weights->sum(), 3);
        $bigFish = $weights->isEmpty() ? 0.0 : round((float) $weights->max(), 3);

        return [
            'total_weight' => $total,
            'big_fish_weight' => $bigFish,
        ];
    }

    /**
     * @return array{total_weight: float, big_fish_weight: float}
     */
    public function playerMatchScore(Player $player, GameMatch $match, bool $approvedOnly): array
    {
        $query = FishCatch::query()
            ->where('match_id', $match->id)
            ->where('player_id', $player->id)
            ->orderByDesc('weight_g');

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        $weights = $query->limit(3)->pluck('weight_g')->map(fn ($w) => (float) $w);
        $total = round($weights->sum(), 3);
        $bigFish = $weights->isEmpty() ? 0.0 : round((float) $weights->max(), 3);

        return [
            'total_weight' => $total,
            'big_fish_weight' => $bigFish,
        ];
    }

    /**
     * @return list<array{team_id: int, total_weight: float, big_fish_weight: float, rank: int, points: int}>
     */
    public function rankTeams(Collection $teams, bool $approvedOnly): array
    {
        $rows = $teams->map(function (Team $team) use ($approvedOnly) {
            $score = $this->teamScore($team, $approvedOnly);
            $approvedCount = $team->catches()
                ->where('approval_status', CatchApprovalStatus::Approved)
                ->count();

            return [
                'team_id' => $team->id,
                'total_weight' => $score['total_weight'],
                'big_fish_weight' => $score['big_fish_weight'],
                'approved_catch_count' => $approvedCount,
            ];
        })->values()->all();

        return $this->assignRanksAndPoints($rows, $approvedOnly);
    }

    /**
     * @return list<array{player_id: int, total_weight: float, big_fish_weight: float, rank: int, points: int}>
     */
    public function rankPlayers(GameMatch $match, Collection $players, bool $approvedOnly): array
    {
        $rows = $players->map(function (Player $player) use ($match, $approvedOnly) {
            $score = $this->playerMatchScore($player, $match, $approvedOnly);
            $approvedCount = FishCatch::query()
                ->where('match_id', $match->id)
                ->where('player_id', $player->id)
                ->where('approval_status', CatchApprovalStatus::Approved)
                ->count();

            return [
                'player_id' => $player->id,
                'total_weight' => $score['total_weight'],
                'big_fish_weight' => $score['big_fish_weight'],
                'approved_catch_count' => $approvedCount,
            ];
        })->values()->all();

        return $this->assignRanksAndPointsForPlayers($rows, $approvedOnly);
    }

    /**
     * @param  list<array{team_id: int, total_weight: float, big_fish_weight: float, approved_catch_count: int}>  $rows
     * @return list<array{team_id: int, total_weight: float, big_fish_weight: float, rank: int, points: int}>
     */
    private function assignRanksAndPoints(array $rows, bool $approvedOnly): array
    {
        usort($rows, function (array $a, array $b): int {
            $tw = $b['total_weight'] <=> $a['total_weight'];
            if ($tw !== 0) {
                return $tw;
            }

            return $b['big_fish_weight'] <=> $a['big_fish_weight'];
        });

        $this->applyRankPoints($rows, $approvedOnly);

        return $rows;
    }

    /**
     * @param  list<array{player_id: int, total_weight: float, big_fish_weight: float, approved_catch_count: int}>  $rows
     * @return list<array{player_id: int, total_weight: float, big_fish_weight: float, rank: int, points: int}>
     */
    private function assignRanksAndPointsForPlayers(array $rows, bool $approvedOnly): array
    {
        usort($rows, function (array $a, array $b): int {
            $tw = $b['total_weight'] <=> $a['total_weight'];
            if ($tw !== 0) {
                return $tw;
            }

            return $b['big_fish_weight'] <=> $a['big_fish_weight'];
        });

        $this->applyRankPoints($rows, $approvedOnly);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function applyRankPoints(array &$rows, bool $approvedOnly): void
    {
        $n = count($rows);
        $i = 0;
        $rank = 1;

        while ($i < $n) {
            $j = $i;
            while ($j + 1 < $n
                && $this->sameScore($rows[$i], $rows[$j + 1])) {
                $j++;
            }

            $points = self::pointsForRank($rank);
            for ($k = $i; $k <= $j; $k++) {
                $rows[$k]['rank'] = $rank;
                $rows[$k]['points'] = $points;
            }

            $rank += ($j - $i + 1);
            $i = $j + 1;
        }

        if ($approvedOnly) {
            foreach ($rows as &$row) {
                if ($row['approved_catch_count'] === 0) {
                    $row['points'] = 1;
                }
            }
            unset($row);
        }

        foreach ($rows as &$row) {
            unset($row['approved_catch_count']);
        }
        unset($row);
    }

    public static function pointsForRank(int $rank): int
    {
        return match ($rank) {
            1 => 6,
            2 => 5,
            3 => 4,
            4 => 3,
            5 => 2,
            default => 1,
        };
    }

    private function sameScore(array $a, array $b): bool
    {
        return abs($a['total_weight'] - $b['total_weight']) < 0.5
            && abs($a['big_fish_weight'] - $b['big_fish_weight']) < 0.5;
    }
}
