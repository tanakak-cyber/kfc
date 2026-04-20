<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchType;
use App\Http\Controllers\Controller;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\AutoTeamBuilderService;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MatchTeamManageController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults,
        private AutoTeamBuilderService $autoTeams
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

        $surveyDateYesAllowlist = $gameMatch->surveyDateYesPlayerIdAllowlist();
        $autoTeamFormUsesSurveyAllowlist = $surveyDateYesAllowlist !== null;
        $autoTeamPlayers = $surveyDateYesAllowlist === null
            ? $players
            : $players->filter(function (Player $p) use ($surveyDateYesAllowlist) {
                return in_array($p->id, $surveyDateYesAllowlist, true);
            })->values();

        $seasonTotals = $this->matchResults->playerTotalsForSeason($gameMatch->season);

        $playerIdToTeamName = [];
        foreach ($gameMatch->teams as $team) {
            foreach ($team->players as $p) {
                $playerIdToTeamName[$p->id] = $team->name;
            }
        }

        $hasMatchCatches = FishCatch::query()->where('match_id', $gameMatch->id)->exists();

        return view('admin.match_teams.index', compact(
            'gameMatch',
            'players',
            'autoTeamPlayers',
            'autoTeamFormUsesSurveyAllowlist',
            'seasonTotals',
            'playerIdToTeamName',
            'hasMatchCatches'
        ));
    }

    public function autoForm(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->match_type === MatchType::Individual) {
            return back()->withErrors(['player_ids' => '個人戦ではチームを編成できません。']);
        }

        if ($gameMatch->is_finalized) {
            return back()->withErrors(['player_ids' => '確定済みの試合ではチーム編成を変更できません。']);
        }

        $validated = $request->validate([
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => ['integer', 'exists:players,id'],
            'replace_existing' => ['sometimes', 'boolean'],
        ]);

        $playerIds = array_values(array_unique(array_map('intval', $validated['player_ids'])));

        if ($playerIds === []) {
            return back()->withErrors(['player_ids' => '選手を1人以上選んでください。']);
        }

        $replaceExisting = $request->boolean('replace_existing');

        if ($gameMatch->teams()->exists() && ! $replaceExisting) {
            return back()->withErrors([
                'player_ids' => 'すでにチームが登録されています。自動編成で作り直す場合は「既存チームを削除してから再編成」にチェックを入れるか、手動でチームを削除してください。',
            ]);
        }

        if ($replaceExisting && FishCatch::query()->where('match_id', $gameMatch->id)->exists()) {
            return back()->withErrors([
                'player_ids' => 'この試合に釣果があるため、チームの一括作り直しはできません（チーム削除で釣果も失われます）。各チームの「手動で編集」でメンバーを入れ替えてください。',
            ]);
        }

        $players = Player::query()->whereIn('id', $playerIds)->get();

        if ($players->count() !== count($playerIds)) {
            return back()->withErrors(['player_ids' => '無効な選手が含まれています。']);
        }

        $surveyAllowlist = $gameMatch->surveyDateYesPlayerIdAllowlist();
        if ($surveyAllowlist !== null) {
            $allowed = array_flip($surveyAllowlist);
            foreach ($playerIds as $pid) {
                if (! isset($allowed[$pid])) {
                    return back()->withErrors([
                        'player_ids' => 'アンケートで候補日に出席（〇）と答えた選手のみ自動編成に含めます。',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($gameMatch, $players, $replaceExisting): void {
            if ($replaceExisting) {
                $gameMatch->teams()->delete();
            }

            $sorted = $this->autoTeams->orderPlayersBySeasonStanding($players, (int) $gameMatch->season_id);
            $groups = $this->autoTeams->pairPlayersIntoTeams($sorted);

            foreach ($groups as $index => $memberIds) {
                $team = Team::query()->create([
                    'match_id' => $gameMatch->id,
                    'name' => 'チーム '.($index + 1),
                    'entry_token' => Str::random(32),
                ]);
                $team->players()->sync($memberIds);
            }
        });

        $gameMatch->load('teams');
        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', 'シーズン成績に基づきチームを自動編成しました。必要に応じて下の「チーム名・メンバーを変更」で調整してください。');
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

        if ($this->playersAlreadyAssignedToOtherTeam($gameMatch, $memberIds, null)) {
            return back()
                ->withErrors([
                    'player_a_id' => '選択した選手のうち、すでにこの試合の別チームに登録されている人がいます。同一試合では1チームのみ登録できます。',
                ])
                ->withInput();
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

    public function update(Request $request, GameMatch $gameMatch, Team $team): RedirectResponse
    {
        if ($gameMatch->match_type === MatchType::Individual) {
            return back()->withErrors(['name' => '個人戦ではチームを編集できません。']);
        }

        if ($gameMatch->is_finalized) {
            return back()->withErrors(['name' => '確定済みの試合のチームは変更できません。']);
        }

        if ($team->match_id !== $gameMatch->id) {
            abort(404);
        }

        $tid = $team->id;

        if (! $request->filled("member_b_id_{$tid}")) {
            $request->merge(["member_b_id_{$tid}" => null]);
        }

        $data = $request->validate([
            "team_name_{$tid}" => ['required', 'string', 'max:255'],
            "member_a_id_{$tid}" => ['required', 'integer', 'exists:players,id'],
            "member_b_id_{$tid}" => ['nullable', 'integer', 'exists:players,id', "different:member_a_id_{$tid}"],
        ]);

        $memberIds = [(int) $data["member_a_id_{$tid}"]];
        if ($data["member_b_id_{$tid}"] !== null) {
            $memberIds[] = (int) $data["member_b_id_{$tid}"];
        }

        if ($this->playersAlreadyAssignedToOtherTeam($gameMatch, $memberIds, $team->id)) {
            return back()
                ->withErrors([
                    "member_a_id_{$tid}" => '選択した選手のうち、すでにこの試合の別チームに登録されている人がいます。',
                ])
                ->withInput();
        }

        $team->update([
            'name' => $data["team_name_{$tid}"],
        ]);
        $team->players()->sync($memberIds);

        $this->matchResults->syncMatch($gameMatch, true);

        return back()->with('status', 'チーム情報を更新しました。');
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

    /**
     * @param  list<int>  $memberIds
     */
    private function playersAlreadyAssignedToOtherTeam(GameMatch $gameMatch, array $memberIds, ?int $exceptTeamId): bool
    {
        return TeamMember::query()
            ->whereHas('team', function ($q) use ($gameMatch, $exceptTeamId) {
                $q->where('match_id', $gameMatch->id);
                if ($exceptTeamId !== null) {
                    $q->where('id', '!=', $exceptTeamId);
                }
            })
            ->whereIn('player_id', $memberIds)
            ->exists();
    }
}
