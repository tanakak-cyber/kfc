<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Team;
use App\Services\CatchImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function __construct(
        private CatchImageProcessor $images
    ) {}

    public function show(string $token): View
    {
        $team = Team::query()
            ->where('entry_token', $token)
            ->with(['gameMatch.season', 'players'])
            ->first();

        if ($team !== null) {
            $gameMatch = $team->gameMatch;
            $top3 = $this->top3ForTeam($team, false);

            return view('entry.show', [
                'entryMode' => 'team',
                'team' => $team,
                'participant' => null,
                'gameMatch' => $gameMatch,
                'top3' => $top3,
            ]);
        }

        $participant = MatchParticipant::query()
            ->where('entry_token', $token)
            ->with(['gameMatch.season', 'player'])
            ->firstOrFail();

        $gameMatch = $participant->gameMatch;
        $top3 = $this->top3ForPlayer($gameMatch, $participant->player_id, false);

        return view('entry.show', [
            'entryMode' => 'individual',
            'team' => null,
            'participant' => $participant,
            'gameMatch' => $gameMatch,
            'top3' => $top3,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        if (Team::query()->where('entry_token', $token)->exists()) {
            return $this->storeTeamEntry($request, $token);
        }

        return $this->storeIndividualEntry($request, $token);
    }

    private function storeTeamEntry(Request $request, string $token): RedirectResponse
    {
        $team = Team::query()
            ->where('entry_token', $token)
            ->with(['gameMatch', 'players'])
            ->firstOrFail();

        if ($team->gameMatch->is_finalized) {
            return back()->withErrors(['match' => 'この試合は結果確定済みのため投稿できません。']);
        }

        $playerIds = $team->players->pluck('id')->all();

        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'in:'.implode(',', $playerIds)],
            'length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:999'],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);

        try {
            DB::transaction(function () use ($request, $team, $validated): void {
                $catch = FishCatch::query()->create([
                    'match_id' => $team->match_id,
                    'team_id' => $team->id,
                    'player_id' => (int) $validated['player_id'],
                    'length_cm' => $validated['length_cm'],
                    'weight_kg' => $validated['weight_kg'],
                    'approval_status' => CatchApprovalStatus::Pending,
                ]);

                foreach ($request->file('photos', []) as $sort => $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $path = $this->images->processAndStore($file);
                    $catch->images()->create([
                        'path' => $path,
                        'sort_order' => (int) $sort,
                    ]);
                }

                if ($catch->images()->count() === 0) {
                    throw ValidationException::withMessages([
                        'photos' => ['画像を1枚以上正しくアップロードしてください。'],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', '釣果を送信しました。承認後に公開されます。');
    }

    private function storeIndividualEntry(Request $request, string $token): RedirectResponse
    {
        $participant = MatchParticipant::query()
            ->where('entry_token', $token)
            ->with('gameMatch')
            ->firstOrFail();

        if (! $participant->is_present) {
            return back()->withErrors(['match' => '欠席のため投稿できません。']);
        }

        if ($participant->gameMatch->is_finalized) {
            return back()->withErrors(['match' => 'この試合は結果確定済みのため投稿できません。']);
        }

        $validated = $request->validate([
            'length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:999'],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);

        try {
            DB::transaction(function () use ($request, $participant, $validated): void {
                $catch = FishCatch::query()->create([
                    'match_id' => $participant->match_id,
                    'team_id' => null,
                    'player_id' => $participant->player_id,
                    'length_cm' => $validated['length_cm'],
                    'weight_kg' => $validated['weight_kg'],
                    'approval_status' => CatchApprovalStatus::Pending,
                ]);

                foreach ($request->file('photos', []) as $sort => $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $path = $this->images->processAndStore($file);
                    $catch->images()->create([
                        'path' => $path,
                        'sort_order' => (int) $sort,
                    ]);
                }

                if ($catch->images()->count() === 0) {
                    throw ValidationException::withMessages([
                        'photos' => ['画像を1枚以上正しくアップロードしてください。'],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', '釣果を送信しました。承認後に公開されます。');
    }

    /**
     * @return list<array{weight_kg: string, length_cm: string}>
     */
    private function top3ForTeam(Team $team, bool $approvedOnly): array
    {
        $query = $team->catches()->orderByDesc('weight_kg');

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        return $query->limit(3)
            ->get(['weight_kg', 'length_cm'])
            ->map(fn (FishCatch $c) => [
                'weight_kg' => (string) $c->weight_kg,
                'length_cm' => (string) $c->length_cm,
            ])
            ->all();
    }

    /**
     * @return list<array{weight_kg: string, length_cm: string}>
     */
    private function top3ForPlayer(GameMatch $match, int $playerId, bool $approvedOnly): array
    {
        $query = FishCatch::query()
            ->where('match_id', $match->id)
            ->where('player_id', $playerId)
            ->orderByDesc('weight_kg');

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        return $query->limit(3)
            ->get(['weight_kg', 'length_cm'])
            ->map(fn (FishCatch $c) => [
                'weight_kg' => (string) $c->weight_kg,
                'length_cm' => (string) $c->length_cm,
            ])
            ->all();
    }
}
