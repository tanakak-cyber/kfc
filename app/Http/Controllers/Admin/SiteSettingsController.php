<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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

        return view('admin.site.edit', [
            'teamName' => $teamName,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:120'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => SiteTeamName::SETTING_KEY],
            ['value' => $data['team_name']]
        );

        SiteTeamName::forgetCache();

        return redirect()
            ->route('admin.site.edit')
            ->with('status', 'チーム名を保存しました。');
    }
}
