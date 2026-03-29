<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Season;
use Illuminate\Support\Collection;

class AutoTeamBuilderService
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    /**
     * シーズン内ポイント降順、同点は name 昇順で並べ替えたプレイヤーコレクションを返す。
     *
     * @param  Collection<int, Player>  $players
     * @return Collection<int, Player>
     */
    public function orderPlayersBySeasonStanding(Collection $players, int $seasonId): Collection
    {
        if ($players->isEmpty()) {
            return $players;
        }

        $ids = $players->pluck('id')->all();

        $season = Season::query()->find($seasonId);
        $allTotals = $season !== null
            ? $this->matchResults->playerTotalsForSeason($season)
            : [];
        $points = [];
        foreach ($ids as $id) {
            $points[$id] = (int) ($allTotals[(int) $id] ?? 0);
        }

        return $players
            ->sort(function (Player $a, Player $b) use ($points): int {
                $pa = (int) ($points[$a->id] ?? 0);
                $pb = (int) ($points[$b->id] ?? 0);
                if ($pa !== $pb) {
                    return $pb <=> $pa;
                }

                return strcmp($a->name, $b->name);
            })
            ->values();
    }

    /**
     * 先頭と末尾をペアにする（奇数は最後が1人チーム）。
     * 入力は既に希望順（例: 成績順）に並んでいること。
     *
     * @param  Collection<int, Player>  $sortedPlayers
     * @return list<list<int>> 各要素は player_id の配列（1〜2人）
     */
    public function pairPlayersIntoTeams(Collection $sortedPlayers): array
    {
        $ids = $sortedPlayers->pluck('id')->all();
        $n = count($ids);
        if ($n === 0) {
            return [];
        }

        $out = [];
        $i = 0;
        $j = $n - 1;

        while ($i <= $j) {
            if ($i === $j) {
                $out[] = [$ids[$i]];
                break;
            }
            $out[] = [$ids[$i], $ids[$j]];
            $i++;
            $j--;
        }

        return $out;
    }
}
