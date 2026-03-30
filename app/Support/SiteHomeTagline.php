<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SiteHomeTagline
{
    public const CACHE_KEY = 'setting.home_tagline';

    public const SETTING_KEY = 'home_tagline';

    public const DEFAULT = 'シーズン・試合・チーム・釣果を一元管理。試合ごとに順位の基準（重さ／長さ）と本数（1〜30本）を設定できます。';

    public static function get(): string
    {
        return once(function (): string {
            return Cache::rememberForever(self::CACHE_KEY, function (): string {
                $v = Setting::query()->where('key', self::SETTING_KEY)->value('value');

                return filled($v) ? (string) $v : self::DEFAULT;
            });
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
