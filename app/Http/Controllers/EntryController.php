<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Models\FishCatch;
use App\Models\Team;
use App\Services\CatchImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->firstOrFail();

        $gameMatch = $team->gameMatch;
        $top3 = $this->top3ForTeam($team, false);

        return view('entry.show', compact('team', 'gameMatch', 'top3'));
    }

    public function store(Request $request, string $token): RedirectResponse
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
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $path = $this->images->processAndStore($request->file('photo'));

        $catch = FishCatch::query()->create([
            'match_id' => $team->match_id,
            'team_id' => $team->id,
            'player_id' => (int) $validated['player_id'],
            'length_cm' => $validated['length_cm'],
            'weight_kg' => $validated['weight_kg'],
            'approval_status' => CatchApprovalStatus::Pending,
        ]);

        $catch->images()->create([
            'path' => $path,
            'sort_order' => 0,
        ]);

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
}
