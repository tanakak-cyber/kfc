<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SiteNoindex
{
    public const CACHE_KEY = 'setting.site_noindex';

    public const SETTING_KEY = 'site_noindex';

    public static function enabled(): bool
    {
        return once(function (): bool {
            return Cache::rememberForever(self::CACHE_KEY, function (): bool {
                $v = Setting::query()->where('key', self::SETTING_KEY)->value('value');

                return $v === '1' || $v === 'true';
            });
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
