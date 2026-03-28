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
            ]);

        return view('admin.settings.edit', compact('siteSetting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $siteSetting = SiteSetting::query()->first()
            ?? SiteSetting::query()->create([
                'site_name' => null,
                'logo_path' => null,
            ]);

        $request->validate([
            'site_name' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        $siteSetting->site_name = $request->input('site_name') ?: null;

        if ($request->hasFile('logo')) {
            if (filled($siteSetting->logo_path)) {
                Storage::disk('public')->delete($siteSetting->logo_path);
            }
            $siteSetting->logo_path = $request->file('logo')->store('site', 'public');
        }

        $siteSetting->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'ロゴ・サイト名を保存しました。');
    }
}
