<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
use Illuminate\View\View;

class PublicMatchController extends Controller
{
    public function show(GameMatch $gameMatch): View
    {
        $gameMatch->load([
            'season',
            'teams.players',
            'matchResults' => fn ($q) => $q->orderBy('rank')->with('team.players'),
        ]);

        $catches = FishCatch::query()
            ->where('match_id', $gameMatch->id)
            ->where('approval_status', CatchApprovalStatus::Approved)
            ->with(['player', 'team', 'images'])
            ->orderByDesc('weight_kg')
            ->get();

        return view('matches.show', compact('gameMatch', 'catches'));
    }
}
