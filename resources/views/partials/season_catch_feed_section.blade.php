{{-- 期待: $seasonCatchFeed (FishCatch のコレクション), 任意 $catchFeedTitle --}}
@php
    $catchFeedTitle = $catchFeedTitle ?? '今シーズンの釣果情報';
@endphp
<section class="kfc-card mt-10">
    <h2 class="kfc-section-title">{{ $catchFeedTitle }}</h2>
    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        @forelse ($seasonCatchFeed as $catch)
            @php
                $catchUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
            @endphp
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-md shadow-zinc-950/5 ring-1 ring-zinc-950/[0.03]">
                @include('partials.catch_image_slider', ['urls' => $catchUrls, 'sliderId' => 'feed-catch-'.$catch->id, 'roundedTop' => true])
                @if (count($catchUrls) === 0)
                    <div class="flex min-h-[8rem] items-center justify-center rounded-t-2xl bg-zinc-100 text-sm text-zinc-500">写真はありません</div>
                @endif
                <div class="border-t border-zinc-100 p-4 text-sm">
                    <p class="text-xs text-zinc-500">
                        <a href="{{ route('matches.show', $catch->gameMatch) }}" class="kfc-link">{{ $catch->gameMatch->title }}</a>
                        <span class="text-zinc-400"> · </span>{{ $catch->gameMatch->held_at->format('Y/m/d') }}
                    </p>
                    <p class="mt-2 font-semibold text-zinc-900">
                        <a href="{{ route('players.show', $catch->player) }}" class="kfc-link">{{ $catch->player->displayLabel() }}</a>
                        @if ($catch->team)
                            <span class="font-normal text-zinc-500">（{{ $catch->team->name }}）</span>
                        @endif
                    </p>
                    <p class="mt-1 text-zinc-600">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_kg }} kg</p>
                </div>
            </div>
        @empty
            <p class="kfc-muted sm:col-span-2">承認済みの釣果はまだありません。</p>
        @endforelse
    </div>
</section>
