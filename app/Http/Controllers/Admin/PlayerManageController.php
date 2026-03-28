<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlayerManageController extends Controller
{
    public function index(): View
    {
        $players = Player::query()->orderBy('name')->paginate(30);

        return view('admin.players.index', compact('players'));
    }

    public function create(): View
    {
        return view('admin.players.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('player-icons', 'public');
        }
        Player::query()->create($data);

        return redirect()->route('admin.players.index')->with('status', '選手を登録しました。');
    }

    public function edit(Player $player): View
    {
        return view('admin.players.edit', compact('player'));
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('icon')) {
            if ($player->icon) {
                Storage::disk('public')->delete($player->icon);
            }
            $data['icon'] = $request->file('icon')->store('player-icons', 'public');
        } else {
            unset($data['icon']);
        }
        $player->update($data);

        return redirect()->route('admin.players.index')->with('status', '選手を更新しました。');
    }

    public function destroy(Player $player): RedirectResponse
    {
        if ($player->icon) {
            Storage::disk('public')->delete($player->icon);
        }
        $player->delete();

        return redirect()->route('admin.players.index')->with('status', '選手を削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);
    }
}
