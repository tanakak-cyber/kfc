<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\SiteHomeTagline;
use App\Support\SiteTeamName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        $teamName = Setting::query()
            ->where('key', SiteTeamName::SETTING_KEY)
            ->value('value');

        if ($teamName === null || $teamName === '') {
            $teamName = (string) config('app.name');
        }

        $homeTagline = Setting::query()
            ->where('key', SiteHomeTagline::SETTING_KEY)
            ->value('value');

        if ($homeTagline === null || $homeTagline === '') {
            $homeTagline = SiteHomeTagline::DEFAULT;
        }

        return view('admin.site.edit', [
            'teamName' => $teamName,
            'homeTagline' => $homeTagline,
            'homeTaglineDefault' => SiteHomeTagline::DEFAULT,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:120'],
            'home_tagline' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => SiteTeamName::SETTING_KEY],
            ['value' => $data['team_name']]
        );

        $tagline = isset($data['home_tagline']) ? trim((string) $data['home_tagline']) : '';
        if ($tagline === '') {
            Setting::query()->where('key', SiteHomeTagline::SETTING_KEY)->delete();
        } else {
            Setting::query()->updateOrCreate(
                ['key' => SiteHomeTagline::SETTING_KEY],
                ['value' => $tagline]
            );
        }

        SiteTeamName::forgetCache();
        SiteHomeTagline::forgetCache();

        return redirect()
            ->route('admin.site.edit')
            ->with('status', 'サイト設定を保存しました。');
    }
}
