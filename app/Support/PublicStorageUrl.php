<?php

namespace App\Support;

final class PublicStorageUrl
{
    /**
     * public/storage 配下のファイル用URL。
     * asset() を使い、サブディレクトリ配下（例: /kfc/）・非標準ポートでも正しく解決する。
     */
    public static function fromDiskPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return asset('storage/'.$normalized);
    }
}
