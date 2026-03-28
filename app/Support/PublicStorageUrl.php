<?php

namespace App\Support;

final class PublicStorageUrl
{
    /**
     * APP_URL に依存しないルート相対URL（127.0.0.1 / localhost の食い違いで画像が壊れるのを防ぐ）
     */
    public static function fromDiskPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return '/storage/'.$normalized;
    }
}
