<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\MatchResult;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlayerProfileController extends Controller
{
    public function show(Player $player): View
    {
        $approvedQuery = FishCatch::query()
            ->where('player_id', $player->id)
            ->where('approval_status', CatchApprovalStatus::Approved);

        $totalCatches = (clone $approvedQuery)->count();
        $maxLength = (clone $approvedQuery)->max('length_cm');
        $maxWeight = (clone $approvedQuery)->max('weight_g');

        // 承認済み釣果を試合別に集計（本数・最長・最大重量）
        $catchGroups = FishCatch::query()
            ->where('player_id', $player->id)
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->with(['gameMatch.season', 'team'])
            ->get()
            ->groupBy('match_id');

        /** @var Collection<int, array{count: int, max_length: mixed, max_weight: mixed}> $statsByMatchId */
        $statsByMatchId = $catchGroups->map(function (Collection $group) {
            return [
                'count' => $group->count(),
                'max_length' => $group->max('length_cm'),
                'max_weight' => $group->max('weight_g'),
            ];
        });

        /*
         * 試合別成績の行は「順位表に載る試合」をすべて出す（釣果ゼロでも表示）。
         * 以前は承認済み釣果がある試合だけだったため、釣果が無い試合が落ちていた。
         */
        /** @var array<int, array{match: \App\Models\GameMatch, rank: int|null}> $rowsByMatchId */
        $rowsByMatchId = [];

        foreach (MatchResult::query()
            ->where('player_id', $player->id)
            ->with(['gameMatch.season'])
            ->get() as $mr) {
            $mid = (int) $mr->match_id;
            $rowsByMatchId[$mid] = [
                'match' => $mr->gameMatch,
                'rank' => $mr->rank !== null ? (int) $mr->rank : null,
            ];
        }

        // team_members のみ参照し join しない（teams / team_members 両方の id で SQLite が ambiguous になるのを避ける）
        $teamIds = DB::table('team_members')
            ->where('player_id', $player->id)
            ->pluck('team_id');
        if ($teamIds->isNotEmpty()) {
            foreach (MatchResult::query()
                ->whereNotNull('team_id')
                ->whereIn('team_id', $teamIds)
                ->with(['gameMatch.season'])
                ->get() as $mr) {
                $mid = (int) $mr->match_id;
                if (! isset($rowsByMatchId[$mid])) {
                    $rowsByMatchId[$mid] = [
                        'match' => $mr->gameMatch,
                        'rank' => $mr->rank !== null ? (int) $mr->rank : null,
                    ];
                }
            }
        }

        // 順位表に無いが釣果だけある試合（データ不整合時のフォールバック）
        foreach ($catchGroups as $matchId => $group) {
            $mid = (int) $matchId;
            if (! isset($rowsByMatchId[$mid])) {
                $rowsByMatchId[$mid] = [
                    'match' => $group->first()->gameMatch,
                    'rank' => null,
                ];
            }
        }

        $perMatch = collect($rowsByMatchId)
            ->map(function (array $row, int $mid) use ($statsByMatchId) {
                /** @var array{count: int, max_length: mixed, max_weight: mixed}|null $stats */
                $stats = $statsByMatchId->get($mid);

                return [
                    'match' => $row['match'],
                    'rank' => $row['rank'],
                    'count' => $stats['count'] ?? 0,
                    'max_length' => $stats['max_length'] ?? null,
                    'max_weight' => $stats['max_weight'] ?? null,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['match']->start_datetime)
            ->values();

        $playerCatches = FishCatch::query()
            ->where('player_id', $player->id)
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->with(['gameMatch.season', 'team', 'images'])
            ->get()
            ->sortByDesc(fn (FishCatch $c) => ($c->gameMatch->start_datetime->timestamp * 1_000_000) + $c->id)
            ->values();

        return view('players.show', compact(
            'player',
            'totalCatches',
            'maxLength',
            'maxWeight',
            'perMatch',
            'playerCatches'
        ));
    }
}
