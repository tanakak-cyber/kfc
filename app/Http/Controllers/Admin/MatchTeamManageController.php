<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MatchTeamManageController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    public function index(GameMatch $gameMatch): View
    {
        $gameMatch->load(['season', 'teams.players']);
        $players = Player::query()->orderBy('name')->get();

        return view('admin.match_teams.index', compact('gameMatch', 'players'));
    }

    public function store(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->withErrors(['name' => '確定済みの試合にチームは追加できません。']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'player_a_id' => ['required', 'exists:players,id'],
            'player_b_id' => ['required', 'exists:players,id', 'different:player_a_id'],
        ]);

        $team = Team::query()->create([
            'match_id' => $gameMatch->id,
            'name' => $data['name'],
            'entry_token' => Str::random(32),
        ]);
        $team->players()->sync([$data['player_a_id'], $data['player_b_id']]);

        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', 'チームを追加し、投稿URLを発行しました。');
    }

    public function destroy(GameMatch $gameMatch, Team $team): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->withErrors(['team' => '確定済みの試合からチームは削除できません。']);
        }

        if ($team->match_id !== $gameMatch->id) {
            abort(404);
        }

        $team->delete();
        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', 'チームを削除しました。');
    }
}
