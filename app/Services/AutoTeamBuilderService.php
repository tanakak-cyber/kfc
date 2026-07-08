<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Season;
use App\Support\SeasonPlayerCatchStats;
use App\Support\SeasonPlayerStandings;
use Illuminate\Support\Collection;

class AutoTeamBuilderService
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    /**
     * 全順位ロジック共通の並び順で並べ替えたプレイヤーコレクションを返す。
     *   1. 対象シーズンの合計ポイント 降順
     *   2. シーズン内での最大重量（g）降順
     *   3. シーズン通算釣果数 降順
     *   （最終タイブレークは player_id 昇順）
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

        $catchStats = SeasonPlayerCatchStats::statsByPlayerId($seasonId);

        return $players
            ->sort(function (Player $a, Player $b) use ($points, $catchStats): int {
                $statsA = $catchStats->get($a->id);
                $statsB = $catchStats->get($b->id);

                return SeasonPlayerStandings::compareStanding(
                    (int) ($points[$a->id] ?? 0),
                    SeasonPlayerStandings::maxWeightValue($statsA),
                    (int) data_get($statsA, 'catch_count', 0),
                    (int) $a->id,
                    (int) ($points[$b->id] ?? 0),
                    SeasonPlayerStandings::maxWeightValue($statsB),
                    (int) data_get($statsB, 'catch_count', 0),
                    (int) $b->id,
                );
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
