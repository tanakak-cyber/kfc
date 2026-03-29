{{-- サイトロゴと同一URLをファビコンに使用 --}}
@php
    $path = parse_url($siteLogoUrl, PHP_URL_PATH) ?: $siteLogoUrl;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $faviconType = match ($ext) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        default => null,
    };
@endphp
<link rel="icon" href="{{ $siteLogoUrl }}" @if ($faviconType !== null) type="{{ $faviconType }}" @endif>
