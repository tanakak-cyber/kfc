{{-- 1〜3位: SVG をインライン出力（asset 経由だと本番で 404 になりやすいため）。グラデ id は描画ごとに一意。 --}}
@props([
    'rank',
])
@php
    $n = (int) $rank;
    $uid = 'm'.str_replace('-', '', \Illuminate\Support\Str::uuid()->toString());
@endphp
@if ($n === 1)
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 52" width="40" height="44" class="h-10 w-auto max-w-[2.75rem] shrink-0 align-middle object-contain" role="img" aria-label="1位">
        <defs>
            <linearGradient id="mg-{{ $uid }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#FFFBE6"/>
                <stop offset="30%" stop-color="#FFE566"/>
                <stop offset="65%" stop-color="#FFD700"/>
                <stop offset="100%" stop-color="#F0B90B"/>
            </linearGradient>
            <linearGradient id="mr-{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#FFEC99"/>
                <stop offset="100%" stop-color="#F5C400"/>
            </linearGradient>
        </defs>
        <path d="M10 6 L24 18 L38 6 L40 12 L24 24 L8 12 Z" fill="url(#mr-{{ $uid }})" stroke="#D4A017" stroke-width="1"/>
        <circle cx="24" cy="34" r="16" fill="url(#mg-{{ $uid }})" stroke="#D4A017" stroke-width="1.5"/>
        <circle cx="24" cy="34" r="13" fill="none" stroke="#FFF8DC" stroke-width="0.75" opacity="0.9"/>
        <text x="24" y="40" text-anchor="middle" font-family="system-ui,-apple-system,'Segoe UI',sans-serif" font-size="18" font-weight="800" fill="#6B4E00">1</text>
    </svg>
@elseif ($n === 2)
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 52" width="40" height="44" class="h-10 w-auto max-w-[2.75rem] shrink-0 align-middle object-contain" role="img" aria-label="2位">
        <defs>
            <linearGradient id="mg2-{{ $uid }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#FFFFFF"/>
                <stop offset="35%" stop-color="#E8EAED"/>
                <stop offset="70%" stop-color="#B8BCC8"/>
                <stop offset="100%" stop-color="#8E95A8"/>
            </linearGradient>
            <linearGradient id="mr2-{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#F5F6F8"/>
                <stop offset="100%" stop-color="#C5CAD6"/>
            </linearGradient>
        </defs>
        <path d="M10 6 L24 18 L38 6 L40 12 L24 24 L8 12 Z" fill="url(#mr2-{{ $uid }})" stroke="#8B909E" stroke-width="1"/>
        <circle cx="24" cy="34" r="16" fill="url(#mg2-{{ $uid }})" stroke="#7A8194" stroke-width="1.5"/>
        <circle cx="24" cy="34" r="13" fill="none" stroke="#FFFFFF" stroke-width="0.75" opacity="0.85"/>
        <text x="24" y="40" text-anchor="middle" font-family="system-ui,-apple-system,'Segoe UI',sans-serif" font-size="18" font-weight="800" fill="#3D4454">2</text>
    </svg>
@elseif ($n === 3)
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 52" width="40" height="44" class="h-10 w-auto max-w-[2.75rem] shrink-0 align-middle object-contain" role="img" aria-label="3位">
        <defs>
            <linearGradient id="mg3-{{ $uid }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#FFD4A8"/>
                <stop offset="35%" stop-color="#E8A060"/>
                <stop offset="70%" stop-color="#CD7F32"/>
                <stop offset="100%" stop-color="#A65D1E"/>
            </linearGradient>
            <linearGradient id="mr3-{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#F0B078"/>
                <stop offset="100%" stop-color="#C8762E"/>
            </linearGradient>
        </defs>
        <path d="M10 6 L24 18 L38 6 L40 12 L24 24 L8 12 Z" fill="url(#mr3-{{ $uid }})" stroke="#8B4A1E" stroke-width="1"/>
        <circle cx="24" cy="34" r="16" fill="url(#mg3-{{ $uid }})" stroke="#8B4A1E" stroke-width="1.5"/>
        <circle cx="24" cy="34" r="13" fill="none" stroke="#FFE0C2" stroke-width="0.75" opacity="0.85"/>
        <text x="24" y="40" text-anchor="middle" font-family="system-ui,-apple-system,'Segoe UI',sans-serif" font-size="18" font-weight="800" fill="#4A2C0A">3</text>
    </svg>
@else
    {{-- メダル（h-10）と縦位置を揃え、列幅内で中央寄せ --}}
    <span class="inline-flex h-10 w-full min-w-0 items-center justify-center tabular-nums font-semibold text-zinc-900">{{ $rank }}</span>
@endif
