{{-- $section, $showSpacer (前に大きな区切りを付けるか) --}}
@if ($showSpacer)
    <div class="w-full shrink-0" aria-hidden="true">
        <div class="h-14 sm:h-24 md:h-32"></div>
        <div class="h-px w-full bg-zinc-300"></div>
        <div class="h-12 sm:h-16 md:h-24"></div>
    </div>
@endif
<div class="min-w-0">
    @include('partials.catch_rank_section_heading', ['section' => $section, 'headingTag' => 'h4'])
    <div class="@unless ($section['fallback_flat'] ?? false) mt-5 @endunless kfc-catch-photo-grid">
        @forelse ($section['catches'] as $catch)
            @php
                $catchUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
            @endphp
            <div class="kfc-catch-card">
                @include('partials.catch_image_slider', ['urls' => $catchUrls, 'sliderId' => 'feed-catch-'.$catch->id, 'roundedTop' => true])
                @if (count($catchUrls) === 0)
                    <div class="flex min-h-[8rem] items-center justify-center rounded-t-2xl bg-emerald-50 text-sm text-zinc-500">写真はありません</div>
                @endif
                <div class="border-t border-emerald-100/70 p-4 text-sm">
                    <p class="text-base font-bold text-zinc-900">
                        <a href="{{ route('players.show', $catch->player) }}" class="kfc-link">{{ $catch->player->displayLabel() }}</a>
                        @if ($catch->team)
                            <span class="text-xs font-medium text-zinc-500">（{{ $catch->team->name }}）</span>
                        @endif
                    </p>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        <span class="kfc-stat-chip"><span class="text-emerald-600">📏</span> {{ \App\Support\PublicDisplayNumber::upToOneDecimal($catch->length_cm) }} cm</span>
                        <span class="kfc-stat-chip"><span class="text-amber-500">⚖️</span> {{ \App\Support\PublicDisplayNumber::upToOneDecimal($catch->weight_g) }} g</span>
                    </div>
                </div>
            </div>
        @empty
            <p class="kfc-muted col-span-full">
                @if ($section['fallback_flat'] ?? false)
                    承認済みの釣果はまだありません。
                @else
                    この順位には承認済みの釣果はまだありません。
                @endif
            </p>
        @endforelse
    </div>
</div>
