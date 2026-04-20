{{-- $section, $showSpacer (前に大きな区切りを付けるか) --}}
@if ($showSpacer)
    <div class="w-full shrink-0" aria-hidden="true">
        <div class="h-14 sm:h-24 md:h-32"></div>
        <div class="h-px w-full bg-zinc-300"></div>
        <div class="h-12 sm:h-16 md:h-24"></div>
    </div>
@endif
<div class="min-w-0">
    @unless ($section['fallback_flat'] ?? false)
        <h4 class="kfc-heading-4">
            {{ $section['heading'] }}
        </h4>
    @endunless
    <div class="@unless ($section['fallback_flat'] ?? false) mt-5 @endunless grid gap-5 sm:grid-cols-2">
        @forelse ($section['catches'] as $catch)
            @php
                $catchUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
            @endphp
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-md shadow-zinc-950/5 ring-1 ring-zinc-950/[0.03]">
                @include('partials.catch_image_slider', ['urls' => $catchUrls, 'sliderId' => 'feed-catch-'.$catch->id, 'roundedTop' => true])
                @if (count($catchUrls) === 0)
                    <div class="flex min-h-[8rem] items-center justify-center rounded-t-2xl bg-zinc-100 text-sm text-zinc-500">写真はありません</div>
                @endif
                <div class="border-t border-zinc-100 p-4 text-sm">
                    <p class="font-semibold text-zinc-900">
                        <a href="{{ route('players.show', $catch->player) }}" class="kfc-link">{{ $catch->player->displayLabel() }}</a>
                        @if ($catch->team)
                            <span class="font-normal text-zinc-500">（{{ $catch->team->name }}）</span>
                        @endif
                    </p>
                    <p class="mt-1 text-zinc-600">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_g }} g</p>
                </div>
            </div>
        @empty
            <p class="kfc-muted sm:col-span-2">
                @if ($section['fallback_flat'] ?? false)
                    承認済みの釣果はまだありません。
                @else
                    この順位には承認済みの釣果はまだありません。
                @endif
            </p>
        @endforelse
    </div>
</div>
