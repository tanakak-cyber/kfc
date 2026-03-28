<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function edit(): View
    {
        $siteSetting = SiteSetting::query()->first()
            ?? SiteSetting::query()->create([
                'site_name' => null,
                'logo_path' => null,
                'home_hero_image_path' => null,
            ]);

        return view('admin.settings.edit', compact('siteSetting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $siteSetting = SiteSetting::query()->first()
            ?? SiteSetting::query()->create([
                'site_name' => null,
                'logo_path' => null,
                'home_hero_image_path' => null,
            ]);

        $request->validate([
            'site_name' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'home_hero' => ['nullable', 'image', 'max:10240'],
            'remove_home_hero' => ['nullable', 'boolean'],
        ]);

        $siteSetting->site_name = $request->input('site_name') ?: null;

        if ($request->hasFile('logo')) {
            if (filled($siteSetting->logo_path)) {
                Storage::disk('public')->delete($siteSetting->logo_path);
            }
            $siteSetting->logo_path = $request->file('logo')->store('site', 'public');
        }

        if ($request->boolean('remove_home_hero')) {
            if (filled($siteSetting->home_hero_image_path)) {
                Storage::disk('public')->delete($siteSetting->home_hero_image_path);
            }
            $siteSetting->home_hero_image_path = null;
        }

        if ($request->hasFile('home_hero')) {
            if (filled($siteSetting->home_hero_image_path)) {
                Storage::disk('public')->delete($siteSetting->home_hero_image_path);
            }
            $siteSetting->home_hero_image_path = $request->file('home_hero')->store('site', 'public');
        }

        $siteSetting->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'ロゴ・メインビジュアル・サイト名を保存しました。');
    }
}
