<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchType;
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

    public function index(GameMatch $gameMatch): View|RedirectResponse
    {
        if ($gameMatch->match_type === MatchType::Individual) {
            return redirect()
                ->route('admin.matches.participants.index', $gameMatch)
                ->with('status', '個人戦の試合です。参加者設定へ移動しました。');
        }

        $gameMatch->load(['season', 'teams.players']);
        $players = Player::query()->orderBy('name')->get();

        return view('admin.match_teams.index', compact('gameMatch', 'players'));
    }

    public function store(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->match_type === MatchType::Individual) {
            return back()->withErrors(['name' => '個人戦ではチームを追加できません。']);
        }

        if ($gameMatch->is_finalized) {
            return back()->withErrors(['name' => '確定済みの試合にチームは追加できません。']);
        }

        if (! $request->filled('player_b_id')) {
            $request->merge(['player_b_id' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'player_a_id' => ['required', 'integer', 'exists:players,id'],
            'player_b_id' => ['nullable', 'integer', 'exists:players,id', 'different:player_a_id'],
        ]);

        $memberIds = [(int) $data['player_a_id']];
        if ($data['player_b_id'] !== null) {
            $memberIds[] = (int) $data['player_b_id'];
        }

        $team = Team::query()->create([
            'match_id' => $gameMatch->id,
            'name' => $data['name'],
            'entry_token' => Str::random(32),
        ]);
        $team->players()->sync($memberIds);

        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', 'チームを追加し、投稿URLを発行しました。');
    }

    public function destroy(GameMatch $gameMatch, Team $team): RedirectResponse
    {
        if ($gameMatch->match_type === MatchType::Individual) {
            return back()->withErrors(['team' => '個人戦ではチームを削除できません。']);
        }

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
