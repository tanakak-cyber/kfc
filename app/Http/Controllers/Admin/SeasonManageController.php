<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SeasonManageController extends Controller
{
    public function index(): View
    {
        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return view('admin.seasons.index', compact('seasons'));
    }

    public function create(): View
    {
        return view('admin.seasons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('seasons', 'public');
        }
        if (! empty($data['is_current'])) {
            Season::query()->update(['is_current' => false]);
        }
        Season::query()->create($data);

        return redirect()->route('admin.seasons.index')->with('status', 'シーズンを作成しました。');
    }

    public function edit(Season $season): View
    {
        return view('admin.seasons.edit', compact('season'));
    }

    public function update(Request $request, Season $season): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            if ($season->image_path) {
                Storage::disk('public')->delete($season->image_path);
            }
            $data['image_path'] = $request->file('image')->store('seasons', 'public');
        }
        if (! empty($data['is_current'])) {
            Season::query()->where('id', '!=', $season->id)->update(['is_current' => false]);
        }
        $season->update($data);

        return redirect()->route('admin.seasons.index')->with('status', 'シーズンを更新しました。');
    }

    public function destroy(Season $season): RedirectResponse
    {
        if ($season->image_path) {
            Storage::disk('public')->delete($season->image_path);
        }
        $season->delete();

        return redirect()->route('admin.seasons.index')->with('status', 'シーズンを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string'],
            'is_current' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        $data['is_current'] = $request->boolean('is_current');

        return $data;
    }
}
