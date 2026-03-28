<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Season;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameMatchManageController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults
    ) {}

    public function index(Request $request): View
    {
        $seasonId = $request->integer('season_id');
        $matches = GameMatch::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->with('season')
            ->orderByDesc('held_at')
            ->paginate(20)
            ->withQueryString();

        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return view('admin.matches.index', compact('matches', 'seasons', 'seasonId'));
    }

    public function create(Request $request): RedirectResponse|View
    {
        $seasons = Season::query()->orderByDesc('starts_on')->get();
        if ($seasons->isEmpty()) {
            return redirect()
                ->route('admin.seasons.create')
                ->with('status', '先にシーズンを作成してください。');
        }

        $selectedSeasonId = $request->integer('season_id') ?: $seasons->first()->id;

        return view('admin.matches.create', compact('seasons', 'selectedSeasonId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_finalized'] = false;
        $match = GameMatch::query()->create($data);
        $this->matchResults->syncMatch($match, true);

        return redirect()->route('admin.matches.index', ['season_id' => $match->season_id])
            ->with('status', '試合を作成しました。');
    }

    public function edit(GameMatch $gameMatch): View
    {
        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return view('admin.matches.edit', compact('gameMatch', 'seasons'));
    }

    public function update(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->withErrors(['match' => '確定済みの試合は編集できません。']);
        }

        $gameMatch->update($this->validated($request));
        $this->matchResults->syncMatch($gameMatch, true);

        return redirect()->route('admin.matches.index', ['season_id' => $gameMatch->season_id])
            ->with('status', '試合を更新しました。');
    }

    public function finalize(GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->with('status', 'すでに確定済みです。');
        }

        $gameMatch->update([
            'is_finalized' => true,
            'status' => MatchStatus::Completed,
        ]);

        $this->matchResults->syncMatch($gameMatch, true);
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '試合結果を確定し、シーズンポイントを更新しました。');
    }

    /**
     * 所属シーズンの season_player_points を、確定済み試合の match_results から再構築する。
     */
    public function recalculateSeasonPlayerPoints(GameMatch $gameMatch): RedirectResponse
    {
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '「'.$gameMatch->season->name.'」の個人順位を再集計しました。');
    }

    /**
     * 承認済み釣果から match_results を現在のルールで再生成し、続けてシーズン個人順位も更新する。
     * （確定済み試合で古いポイントが残っている場合に使用）
     */
    public function resyncMatchResultsAndSeason(GameMatch $gameMatch): RedirectResponse
    {
        $this->matchResults->syncMatch($gameMatch, true);
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', 'この試合の順位・ポイントを再計算し、シーズン個人順位を更新しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'season_id' => ['required', 'exists:seasons,id'],
            'title' => ['required', 'string', 'max:255'],
            'held_at' => ['required', 'date'],
            'field' => ['required', 'string', 'max:255'],
            'launch_shop' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string'],
            'status' => ['required', 'in:scheduled,in_progress,completed'],
        ]);

        $data['status'] = MatchStatus::from($data['status']);

        return $data;
    }
}
