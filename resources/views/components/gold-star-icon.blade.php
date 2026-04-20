{{-- ビッグ・最大重量など「最大」強調用の金星（インライン SVG・グラデ ID は都度一意） --}}
@php
    $starUid = 'g'.str_replace('-', '', \Illuminate\Support\Str::uuid()->toString());
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} aria-hidden="true">
    <defs>
        <linearGradient id="sg-{{ $starUid }}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#FFF9E6"/>
            <stop offset="45%" stop-color="#FFD700"/>
            <stop offset="100%" stop-color="#F0A000"/>
        </linearGradient>
    </defs>
    <path fill="url(#sg-{{ $starUid }})" stroke="#D4A017" stroke-width="0.6" stroke-linejoin="round" d="M12 2.2l2.8 7.2h7.5l-6 4.6 2.3 7.4L12 16.8 5.4 21.4l2.3-7.4-6-4.6h7.5L12 2.2z"/>
</svg>
