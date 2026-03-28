<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SiteTeamName
{
    public const CACHE_KEY = 'setting.team_name';

    public const SETTING_KEY = 'team_name';

    public static function get(): string
    {
        return once(function (): string {
            return Cache::rememberForever(self::CACHE_KEY, function (): string {
                $v = Setting::query()->where('key', self::SETTING_KEY)->value('value');

                return filled($v) ? (string) $v : (string) config('app.name');
            });
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
