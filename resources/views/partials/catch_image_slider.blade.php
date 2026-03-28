{{-- 期待: $urls (string[]), $sliderId (一意な文字列・例: catch id), 任意 $roundedTop --}}
@php
    $roundedTop = $roundedTop ?? true;
    $roundClass = $roundedTop ? 'rounded-t-2xl' : '';
    $trackId = 'kfc-slider-track-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($sliderId ?? uniqid('s', true)));
@endphp
@if (count($urls) > 0)
    {{-- onclick に @js($urls) を入れると URL の引用符で属性が壊れるため、グローバルに登録して委譲で開く --}}
    <script>
        window.kfcCatchGalleries = window.kfcCatchGalleries || {};
        window.kfcCatchGalleries[@js($trackId)] = @js($urls);
    </script>
    <div class="relative aspect-video w-full overflow-hidden bg-black {{ $roundClass }}">
        <div
            id="{{ $trackId }}"
            class="flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            tabindex="0"
            role="region"
            aria-label="釣果写真のスライダー"
        >
            @foreach ($urls as $idx => $url)
                <div class="relative h-full min-w-full shrink-0 snap-center bg-black">
                    <button
                        type="button"
                        class="flex h-full w-full cursor-zoom-in items-center justify-center border-0 bg-black p-0"
                        data-kfc-lightbox="{{ $trackId }}"
                        data-kfc-lightbox-index="{{ $idx }}"
                    >
                        <img
                            src="{{ $url }}"
                            alt=""
                            class="pointer-events-none max-h-full max-w-full object-contain transition hover:opacity-95"
                            draggable="false"
                        >
                    </button>
                </div>
            @endforeach
        </div>

        @if (count($urls) > 1)
            <button
                type="button"
                class="absolute left-2 top-1/2 z-[2] flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/45 text-xl font-bold text-white shadow-lg backdrop-blur-sm transition hover:bg-black/60 sm:h-11 sm:w-11"
                data-kfc-slider-prev
                data-kfc-slider-target="{{ $trackId }}"
                aria-label="前の写真"
            >‹</button>
            <button
                type="button"
                class="absolute right-2 top-1/2 z-[2] flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/45 text-xl font-bold text-white shadow-lg backdrop-blur-sm transition hover:bg-black/60 sm:h-11 sm:w-11"
                data-kfc-slider-next
                data-kfc-slider-target="{{ $trackId }}"
                aria-label="次の写真"
            >›</button>
            <p class="pointer-events-none absolute bottom-2 left-1/2 z-[1] -translate-x-1/2 rounded-full bg-black/60 px-2.5 py-1 text-[10px] font-medium text-white backdrop-blur-sm sm:text-xs">
                ‹ › または横スワイプ（{{ count($urls) }}枚）
            </p>
        @endif
    </div>
@endif
