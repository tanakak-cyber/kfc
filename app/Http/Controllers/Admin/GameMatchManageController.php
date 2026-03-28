<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchType;
use App\Http\Controllers\Controller;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\MatchPlayerBonusPoint;
use App\Models\Season;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->orderByDesc('start_datetime')
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
        $data = $this->validatedForStore($request);
        $data['is_finalized'] = false;
        $match = GameMatch::query()->create($data);
        $this->matchResults->syncMatch($match, true);

        return redirect()->route('admin.matches.index', ['season_id' => $match->season_id])
            ->with('status', '試合を作成しました。');
    }

    public function edit(GameMatch $gameMatch): View
    {
        $gameMatch->load(['season', 'teams.players', 'matchParticipants.player']);
        $seasons = Season::query()->orderByDesc('starts_on')->get();

        $matchCatches = FishCatch::query()
            ->where('match_id', $gameMatch->id)
            ->with(['team', 'player', 'images'])
            ->orderByDesc('created_at')
            ->get();

        $bonusPoints = $gameMatch->matchPlayerBonusPoints()
            ->with('player')
            ->orderByDesc('id')
            ->get();

        $bonusEligiblePlayers = $gameMatch->playersEligibleForBonus();

        return view('admin.matches.edit', compact(
            'gameMatch',
            'seasons',
            'matchCatches',
            'bonusPoints',
            'bonusEligiblePlayers'
        ));
    }

    public function storePlayerBonusPoint(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        $eligibleIds = $gameMatch->playersEligibleForBonus()->pluck('id')->all();

        if ($eligibleIds === []) {
            return back()->withErrors(['player_id' => 'この試合に登録された選手がいないため、追加ポイントを付与できません。']);
        }

        $data = $request->validate([
            'player_id' => ['required', 'integer', Rule::in($eligibleIds)],
            'points' => ['required', 'integer', 'min:1', 'max:99'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = isset($data['reason']) ? trim((string) $data['reason']) : '';

        MatchPlayerBonusPoint::query()->create([
            'match_id' => $gameMatch->id,
            'player_id' => (int) $data['player_id'],
            'points' => (int) $data['points'],
            'reason' => $reason !== '' ? $reason : null,
        ]);

        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '追加ポイントを登録し、シーズン個人順位を更新しました。');
    }

    public function destroyPlayerBonusPoint(GameMatch $gameMatch, MatchPlayerBonusPoint $bonusPoint): RedirectResponse
    {
        if ($bonusPoint->match_id !== $gameMatch->id) {
            abort(404);
        }

        $season = $gameMatch->season;
        $bonusPoint->delete();
        $this->matchResults->rebuildSeasonPlayerPoints($season);

        return back()->with('status', '追加ポイントを削除し、シーズン個人順位を更新しました。');
    }

    public function update(Request $request, GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->withErrors(['match' => '確定済みの試合は編集できません。']);
        }

        $gameMatch->update($this->validatedForUpdate($request));
        $this->matchResults->syncMatch($gameMatch, true);

        return redirect()->route('admin.matches.index', ['season_id' => $gameMatch->season_id])
            ->with('status', '試合を更新しました。');
    }

    public function destroy(GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return redirect()
                ->route('admin.matches.edit', $gameMatch)
                ->withErrors(['match' => '確定済みの試合は削除できません。先に「確定を解除」してから削除してください。']);
        }

        $season = $gameMatch->season()->firstOrFail();
        $seasonId = $gameMatch->season_id;

        DB::transaction(function () use ($gameMatch): void {
            $catches = FishCatch::query()
                ->where('match_id', $gameMatch->id)
                ->with('images')
                ->get();

            foreach ($catches as $catch) {
                foreach ($catch->images as $img) {
                    Storage::disk('public')->delete($img->path);
                }
            }

            $gameMatch->delete();
        });

        $this->matchResults->rebuildSeasonPlayerPoints($season);

        return redirect()
            ->route('admin.matches.index', ['season_id' => $seasonId])
            ->with('status', '試合を削除しました。');
    }

    public function finalize(GameMatch $gameMatch): RedirectResponse
    {
        if ($gameMatch->is_finalized) {
            return back()->with('status', 'すでに確定済みです。');
        }

        $gameMatch->update([
            'is_finalized' => true,
        ]);

        $this->matchResults->syncMatch($gameMatch, true);
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '試合結果を確定し、シーズンポイントを更新しました。');
    }

    public function unfinalize(GameMatch $gameMatch): RedirectResponse
    {
        if (! $gameMatch->is_finalized) {
            return back()->with('status', 'この試合は未確定です。');
        }

        $gameMatch->update([
            'is_finalized' => false,
        ]);

        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '試合の確定を解除しました。シーズン個人ポイントを再集計しました（この試合のポイントは集計から外れます）。');
    }

    public function recalculateSeasonPlayerPoints(GameMatch $gameMatch): RedirectResponse
    {
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', '「'.$gameMatch->season->name.'」の個人順位を再集計しました。');
    }

    public function resyncMatchResultsAndSeason(GameMatch $gameMatch): RedirectResponse
    {
        $this->matchResults->syncMatch($gameMatch, true);
        $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);

        return back()->with('status', 'この試合の順位・ポイントを再計算し、シーズン個人順位を更新しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedForStore(Request $request): array
    {
        $data = $request->validate([
            'season_id' => ['required', 'exists:seasons,id'],
            'match_type' => ['required', Rule::in(array_map(fn (MatchType $t) => $t->value, MatchType::cases()))],
            'title' => ['required', 'string', 'max:255'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after:start_datetime'],
            'field' => ['required', 'string', 'max:255'],
            'launch_shop' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string'],
        ]);

        $data['match_type'] = MatchType::from($data['match_type']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedForUpdate(Request $request): array
    {
        $data = $request->validate([
            'season_id' => ['required', 'exists:seasons,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after:start_datetime'],
            'field' => ['required', 'string', 'max:255'],
            'launch_shop' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string'],
        ]);

        return $data;
    }
}
