<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatchApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Services\CatchImageProcessor;
use App\Services\MatchResultSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MatchFishCatchController extends Controller
{
    public function __construct(
        private CatchImageProcessor $images,
        private MatchResultSyncService $matchResults
    ) {}

    public function edit(GameMatch $gameMatch, FishCatch $fishCatch): View
    {
        $this->assertCatchBelongsToMatch($gameMatch, $fishCatch);

        $fishCatch->load(['team.players', 'player', 'images']);

        $allowedPlayers = $gameMatch->isIndividualMatch()
            ? MatchParticipant::query()
                ->where('match_id', $gameMatch->id)
                ->with('player')
                ->get()
                ->pluck('player')
                ->filter()
                ->values()
            : ($fishCatch->team?->players ?? collect());

        return view('admin.matches.catches.edit', compact('gameMatch', 'fishCatch', 'allowedPlayers'));
    }

    public function update(Request $request, GameMatch $gameMatch, FishCatch $fishCatch): RedirectResponse
    {
        $this->assertCatchBelongsToMatch($gameMatch, $fishCatch);

        if ($gameMatch->isIndividualMatch()) {
            $playerIds = MatchParticipant::query()
                ->where('match_id', $gameMatch->id)
                ->pluck('player_id')
                ->all();
        } else {
            $fishCatch->load('team.players');
            if ($fishCatch->team === null) {
                return back()->withErrors(['player_id' => 'チームが紐づいていません。']);
            }
            $playerIds = $fishCatch->team->players->pluck('id')->all();
        }

        $validated = $request->validate([
            'player_id' => ['required', 'integer', Rule::in($playerIds)],
            'length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:999'],
            'approval_status' => ['required', Rule::in(array_map(fn (CatchApprovalStatus $c) => $c->value, CatchApprovalStatus::cases()))],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);

        if ($gameMatch->isTeamMatch()) {
            $fishCatch->loadMissing('team.players');
            if ($fishCatch->team !== null && ! $fishCatch->team->players->contains('id', (int) $validated['player_id'])) {
                return back()->withErrors(['player_id' => '選択した選手はこのチームに所属していません。']);
            }
        }

        try {
            DB::transaction(function () use ($request, $fishCatch, $validated): void {
                $removeIds = array_map('intval', $request->input('remove_image_ids', []));
                if ($removeIds !== []) {
                    $toRemove = $fishCatch->images()->whereIn('id', $removeIds)->get();
                    foreach ($toRemove as $img) {
                        Storage::disk('public')->delete($img->path);
                        $img->delete();
                    }
                }

                $baseOrder = (int) ($fishCatch->images()->max('sort_order') ?? -1);
                $addIndex = 0;
                foreach ($request->file('photos', []) as $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $addIndex++;
                    $path = $this->images->processAndStore($file);
                    $fishCatch->images()->create([
                        'path' => $path,
                        'sort_order' => $baseOrder + $addIndex,
                    ]);
                }

                $fishCatch->update([
                    'player_id' => (int) $validated['player_id'],
                    'length_cm' => $validated['length_cm'],
                    'weight_kg' => $validated['weight_kg'],
                    'approval_status' => CatchApprovalStatus::from($validated['approval_status']),
                ]);

                $fishCatch->refresh();

                if ($fishCatch->approval_status === CatchApprovalStatus::Approved && $fishCatch->images()->count() === 0) {
                    throw ValidationException::withMessages([
                        'photos' => ['承認済みの釣果には画像が1枚以上必要です。'],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.matches.catches.edit', [$gameMatch, $fishCatch])
                ->withErrors($e->errors())
                ->withInput();
        }

        $gameMatch->loadMissing('season');

        $this->matchResults->syncMatch($gameMatch, true);
        if ($gameMatch->is_finalized) {
            $this->matchResults->rebuildSeasonPlayerPoints($gameMatch->season);
        }

        return redirect()
            ->route('admin.matches.edit', $gameMatch)
            ->with('status', '釣果を更新し、順位を再計算しました。');
    }

    private function assertCatchBelongsToMatch(GameMatch $gameMatch, FishCatch $fishCatch): void
    {
        if ((int) $fishCatch->match_id !== (int) $gameMatch->id) {
            abort(404);
        }
    }
}
