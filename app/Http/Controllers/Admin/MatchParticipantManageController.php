<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Player;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MatchParticipantManageController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    public function index(GameMatch $gameMatch): View|RedirectResponse
    {
        if ($gameMatch->isTeamMatch()) {
            return redirect()
                ->route('admin.matches.teams.index', $gameMatch)
                ->with('status', 'チーム戦の試合です。チーム設定へ移動しました。');
        }

        $gameMatch->load(['season', 'matchParticipants.player']);

        $players = Player::query()->orderBy('name')->get();

        return view('admin.match_participants.index', compact('gameMatch', 'players'));
    }

    public function store(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->withErrors(['player_id' => '確定済みの試合に参加者は追加できません。']);
        }

        if ($gameMatch->isTeamMatch()) {
            return back()->withErrors(['player_id' => 'チーム戦では参加者管理を使いません。']);
        }

        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
        ]);

        if (MatchParticipant::query()->where('match_id', $gameMatch->id)->where('player_id', $data['player_id'])->exists()) {
            return back()->withErrors(['player_id' => 'この選手はすでに登録されています。']);
        }

        MatchParticipant::query()->create([
            'match_id' => $gameMatch->id,
            'player_id' => (int) $data['player_id'],
            'is_present' => true,
            'entry_token' => Str::random(32),
        ]);

        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', '参加者を追加し、投稿URLを発行しました。');
    }

    public function updatePresence(Request $request, GameMatch $gameMatch, MatchParticipant $participant): RedirectResponse
    {
        if ($participant->match_id !== $gameMatch->id) {
            abort(404);
        }

        if ($gameMatch->is_finalized) {
            return back()->withErrors(['participant' => '確定済みの試合は変更できません。']);
        }

        $data = $request->validate([
            'is_present' => ['required', 'in:0,1'],
        ]);

        $participant->update(['is_present' => (bool) (int) $data['is_present']]);

        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', '出席情報を更新しました。');
    }

    public function destroy(GameMatch $gameMatch, MatchParticipant $participant): RedirectResponse
    {
        if ($participant->match_id !== $gameMatch->id) {
            abort(404);
        }

        if ($gameMatch->is_finalized) {
            return back()->withErrors(['participant' => '確定済みの試合から参加者は削除できません。']);
        }

        $participant->delete();
        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', '参加者を削除しました。');
    }
}
