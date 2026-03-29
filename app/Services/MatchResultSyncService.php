<?php

namespace App\Services;

use App\Enums\MatchType;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\MatchPlayerBonusPoint;
use App\Models\MatchResult;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonPlayerPoint;
use App\Models\TeamMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchResultSyncService
{
    public function __construct(
        private RankingService $ranking
    ) {}

    public function syncMatch(GameMatch $match, bool $approvedOnly = true): void
    {
        if ($match->match_type === MatchType::Individual) {
            $this->syncIndividualMatch($match, $approvedOnly);
        } else {
            $this->syncTeamMatch($match, $approvedOnly);
        }
    }

    private function syncTeamMatch(GameMatch $match, bool $approvedOnly): void
    {
        $teams = $match->teams()->get();
        $rows = $this->ranking->rankTeams($teams, $approvedOnly);

        DB::transaction(function () use ($match, $rows): void {
            MatchResult::query()->where('match_id', $match->id)->delete();

            foreach ($rows as $row) {
                MatchResult::query()->create([
                    'match_id' => $match->id,
                    'team_id' => $row['team_id'],
                    'player_id' => null,
                    'rank' => $row['rank'],
                    'total_weight' => $row['total_weight'],
                    'big_fish_weight' => $row['big_fish_weight'],
                    'points' => $row['points'],
                ]);
            }
        });
    }

    private function syncIndividualMatch(GameMatch $match, bool $approvedOnly): void
    {
        $players = MatchParticipant::query()
            ->where('match_id', $match->id)
            ->where('is_present', true)
            ->with('player')
            ->get()
            ->pluck('player')
            ->filter(fn (?Player $p) => $p !== null)
            ->values();

        $rows = $this->ranking->rankPlayers($match, $players, $approvedOnly);

        DB::transaction(function () use ($match, $rows): void {
            MatchResult::query()->where('match_id', $match->id)->delete();

            foreach ($rows as $row) {
                MatchResult::query()->create([
                    'match_id' => $match->id,
                    'team_id' => null,
                    'player_id' => $row['player_id'],
                    'rank' => $row['rank'],
                    'total_weight' => $row['total_weight'],
                    'big_fish_weight' => $row['big_fish_weight'],
                    'points' => $row['points'],
                ]);
            }
        });
    }

    /**
     * 確定済み試合の match_results と追加ポイントから、シーズン内の選手別合計を算出する。
     * 公開ページの順位表示は常にここ経由にし、キャッシュテーブルと試合ページのズレを防ぐ。
     *
     * @return array<int, int> player_id => total_points
     */
    public function playerTotalsForSeason(Season $season): array
    {
        $totals = [];

        $finalizedMatchIds = GameMatch::query()
            ->where('season_id', $season->id)
            ->where('is_finalized', true)
            ->pluck('id');

        if ($finalizedMatchIds->isEmpty()) {
            foreach (Player::query()->pluck('id') as $playerId) {
                $totals[(int) $playerId] = 0;
            }

            return $totals;
        }

        $results = MatchResult::query()
            ->whereIn('match_id', $finalizedMatchIds)
            ->get(['match_id', 'team_id', 'player_id', 'points', 'rank']);

        foreach ($results->whereNotNull('player_id') as $mr) {
            $pid = (int) $mr->player_id;
            $totals[$pid] = ($totals[$pid] ?? 0) + (int) $mr->points;
        }

        $teamRows = $results->filter(
            fn (MatchResult $mr) => $mr->player_id === null && $mr->team_id !== null
        );

        foreach ($teamRows->groupBy('match_id') as $matchTeamRows) {
            foreach ($this->aggregateTeamMatchRowsToPlayerPoints($matchTeamRows) as $pid => $pts) {
                $totals[$pid] = ($totals[$pid] ?? 0) + $pts;
            }
        }

        $bonusRows = MatchPlayerBonusPoint::query()
            ->whereIn('match_id', $finalizedMatchIds)
            ->get(['player_id', 'points']);

        foreach ($bonusRows as $bonus) {
            $pid = (int) $bonus->player_id;
            $totals[$pid] = ($totals[$pid] ?? 0) + (int) $bonus->points;
        }

        foreach (Player::query()->pluck('id') as $playerId) {
            $pid = (int) $playerId;
            if (! array_key_exists($pid, $totals)) {
                $totals[$pid] = 0;
            }
        }

        return $totals;
    }

    /**
     * 1 試合分のチーム戦 match_results から、選手ごとの順位ポイントを集計する。
     * 同一選手が同一試合の複数チームに誤登録されている場合は、最良順位（rank 最小）の 1 チーム分のみ採用する。
     *
     * @return array<int, int> player_id => points
     */
    public function teamRankPointsPerPlayerForMatch(GameMatch $match): array
    {
        if (! $match->isTeamMatch()) {
            return [];
        }

        $rows = $match->matchResults
            ->filter(fn (MatchResult $mr) => $mr->player_id === null && $mr->team_id !== null)
            ->values();

        return $this->aggregateTeamMatchRowsToPlayerPoints($rows);
    }

    /**
     * @param  Collection<int, MatchResult>  $matchTeamRows 同一 match_id のチーム行のみ
     * @return array<int, int>
     */
    private function aggregateTeamMatchRowsToPlayerPoints(Collection $matchTeamRows): array
    {
        $teamOutcome = [];
        foreach ($matchTeamRows as $mr) {
            $tid = (int) $mr->team_id;
            $teamOutcome[$tid] = [
                'rank' => (int) $mr->rank,
                'points' => (int) $mr->points,
            ];
        }

        if ($teamOutcome === []) {
            return [];
        }

        $teamIds = array_keys($teamOutcome);

        $members = TeamMember::query()
            ->whereIn('team_id', $teamIds)
            ->get(['team_id', 'player_id']);

        $playerToTeams = [];
        foreach ($members as $m) {
            $pid = (int) $m->player_id;
            $playerToTeams[$pid][] = (int) $m->team_id;
        }

        $delta = [];

        foreach ($playerToTeams as $pid => $tids) {
            $tids = array_values(array_unique($tids));
            $best = $this->pickBestTeamOutcome($tids, $teamOutcome);
            if ($best !== null) {
                $delta[$pid] = $best['points'];
            }
        }

        return $delta;
    }

    /**
     * @param  list<int>  $teamIds
     * @param  array<int, array{rank: int, points: int}>  $teamOutcome
     * @return array{team_id: int, rank: int, points: int}|null
     */
    private function pickBestTeamOutcome(array $teamIds, array $teamOutcome): ?array
    {
        $bestTid = null;
        $best = null;

        foreach ($teamIds as $tid) {
            if (! isset($teamOutcome[$tid])) {
                continue;
            }

            $cand = $teamOutcome[$tid];

            if ($best === null) {
                $best = $cand;
                $bestTid = $tid;

                continue;
            }

            if ($cand['rank'] < $best['rank']) {
                $best = $cand;
                $bestTid = $tid;

                continue;
            }

            if ($cand['rank'] === $best['rank'] && $cand['points'] > $best['points']) {
                $best = $cand;
                $bestTid = $tid;
            }
        }

        if ($bestTid === null || $best === null) {
            return null;
        }

        return [
            'team_id' => $bestTid,
            'rank' => $best['rank'],
            'points' => $best['points'],
        ];
    }

    /**
     * 試合ページ「選手別の付与」用。複数チーム所属時は集計と同じく最良順位のみ表示する。
     *
     * @param  Collection<int, int>  $playerBonusTotals
     * @return Collection<int, array{team_name: string, team_rank: int, player: Player, rank_points: int, bonus: int, match_total: int}>
     */
    public function teamMatchPlayerBreakdownRows(GameMatch $gameMatch, Collection $playerBonusTotals): Collection
    {
        if (! $gameMatch->isTeamMatch()) {
            return collect();
        }

        $rows = $gameMatch->matchResults
            ->filter(fn (MatchResult $mr) => $mr->player_id === null && $mr->team_id !== null)
            ->values();

        $teamOutcome = [];
        foreach ($rows as $mr) {
            $tid = (int) $mr->team_id;
            $teamOutcome[$tid] = [
                'rank' => (int) $mr->rank,
                'points' => (int) $mr->points,
            ];
        }

        if ($teamOutcome === []) {
            return collect();
        }

        $teamIds = array_keys($teamOutcome);

        $members = TeamMember::query()
            ->whereIn('team_id', $teamIds)
            ->get(['team_id', 'player_id']);

        $playerToTeams = [];
        foreach ($members as $m) {
            $pid = (int) $m->player_id;
            $playerToTeams[$pid][] = (int) $m->team_id;
        }

        $out = collect();

        foreach ($playerToTeams as $pid => $tids) {
            $tids = array_values(array_unique($tids));
            $best = $this->pickBestTeamOutcome($tids, $teamOutcome);
            if ($best === null) {
                continue;
            }

            $team = $gameMatch->teams->firstWhere('id', $best['team_id']);
            $teamName = $team?->name ?? '—';

            $player = $team?->players->firstWhere('id', $pid);
            if ($player === null) {
                $player = Player::query()->find($pid);
            }

            if ($player === null) {
                continue;
            }

            $bonus = (int) ($playerBonusTotals->get($pid) ?? 0);

            $out->push([
                'team_name' => $teamName,
                'team_rank' => $best['rank'],
                'player' => $player,
                'rank_points' => $best['points'],
                'bonus' => $bonus,
                'match_total' => $best['points'] + $bonus,
            ]);
        }

        return $out
            ->sortBy(fn (array $r) => sprintf(
                '%05d-%s',
                $r['team_rank'],
                mb_strtolower($r['player']->displayLabel())
            ))
            ->values();
    }

    /**
     * 順位表用: 全選手の SeasonPlayerPoint をメモリ上に構築（DB の season_player_points に依存しない）。
     *
     * @return Collection<int, SeasonPlayerPoint>
     */
    public function seasonPlayerStandingModels(Season $season): Collection
    {
        $totals = $this->playerTotalsForSeason($season);

        return Player::query()->orderBy('name')->get()->map(function (Player $player) use ($season, $totals) {
            $row = SeasonPlayerPoint::make([
                'season_id' => $season->id,
                'player_id' => $player->id,
                'total_points' => (int) ($totals[$player->id] ?? 0),
            ]);
            $row->setRelation('player', $player);

            return $row;
        });
    }

    public function rebuildSeasonPlayerPoints(Season $season): void
    {
        DB::transaction(function () use ($season): void {
            SeasonPlayerPoint::query()->where('season_id', $season->id)->delete();

            $totals = $this->playerTotalsForSeason($season);

            foreach ($totals as $playerId => $points) {
                SeasonPlayerPoint::query()->create([
                    'season_id' => $season->id,
                    'player_id' => (int) $playerId,
                    'total_points' => (int) $points,
                ]);
            }
        });
    }
}
