<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\Player;
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
        $maxWeight = (clone $approvedQuery)->max('weight_kg');

        $perMatch = FishCatch::query()
            ->where('player_id', $player->id)
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->with(['gameMatch.season', 'team'])
            ->get()
            ->groupBy(fn (FishCatch $c) => $c->match_id)
            ->map(function ($group) {
                /** @var GameMatch $match */
                $match = $group->first()->gameMatch;

                return [
                    'match' => $match,
                    'count' => $group->count(),
                    'max_length' => $group->max('length_cm'),
                    'max_weight' => $group->max('weight_kg'),
                ];
            })
            ->sortByDesc(fn ($row) => $row['match']->start_datetime)
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
