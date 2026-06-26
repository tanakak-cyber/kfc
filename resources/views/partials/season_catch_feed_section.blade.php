{{-- 期待: $seasonCatchMatchBlocks（試合×順位の入れ子）、任意 $catchFeedTitle --}}
@php
    $catchFeedTitle = $catchFeedTitle ?? '今シーズンの釣果情報';
@endphp
<section class="kfc-card kfc-card--bass kfc-card--bass-faint mt-10">
    <div class="kfc-section-head">
        <h2 class="kfc-section-title">{{ $catchFeedTitle }}</h2>
    </div>

    @forelse ($seasonCatchMatchBlocks as $block)
        @php
            $match = $block['match'];
            $catchSections = $block['catchSections'];
            $primarySections = [];
            $secondarySections = [];
            foreach ($catchSections as $section) {
                $isPrimary = ($section['fallback_flat'] ?? false) || ($section['rank'] ?? null) === 1;
                if ($isPrimary) {
                    $primarySections[] = $section;
                } else {
                    $secondarySections[] = $section;
                }
            }
            $hasSecondary = count($secondarySections) > 0;
            $secondaryPanelId = 'kfc-feed-secondary-'.$match->id;
        @endphp
        <div class="mt-12 min-w-0 first:mt-6">
            <div class="kfc-match-block-head">
                <h3 class="flex items-center gap-2 pl-2 text-base font-extrabold tracking-tight text-emerald-950 sm:text-lg">
                    <span class="inline-block h-2.5 w-2.5 shrink-0 rotate-45 rounded-[2px] bg-gradient-to-br from-emerald-500 to-teal-600" aria-hidden="true"></span>
                    <a href="{{ route('matches.show', $match) }}" class="hover:text-emerald-800 hover:underline">{{ $match->title }}</a>
                </h3>
                <p class="mt-1.5 flex flex-wrap items-center gap-2 pl-2">
                    <span class="kfc-date-badge">{{ $match->start_datetime->format('Y/m/d H:i') }}</span>
                    @if ($match->field)
                        <span class="text-xs text-zinc-500">{{ $match->field }}</span>
                    @endif
                </p>
            </div>

            <div class="mt-6 flex flex-col">
                @foreach ($primarySections as $section)
                    @include('partials.season_catch_feed_rank_section', [
                        'section' => $section,
                        'showSpacer' => ! $loop->first,
                    ])
                @endforeach

                @if ($hasSecondary)
                    <div class="mt-6 flex justify-center">
                        <button
                            type="button"
                            class="kfc-btn-emerald text-xs sm:text-sm"
                            data-kfc-feed-expand
                            data-kfc-feed-panel="{{ $secondaryPanelId }}"
                            aria-expanded="false"
                            aria-controls="{{ $secondaryPanelId }}"
                        >
                            2位以下の釣果を表示
                        </button>
                    </div>

                    <div
                        id="{{ $secondaryPanelId }}"
                        class="kfc-feed-secondary-panel mt-6 flex hidden min-w-0 flex-col"
                    >
                        @foreach ($secondarySections as $section)
                            @php
                                $showSpacer = ! $loop->first || count($primarySections) > 0;
                            @endphp
                            @include('partials.season_catch_feed_rank_section', [
                                'section' => $section,
                                'showSpacer' => $showSpacer,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="mt-6 kfc-muted">承認済みの釣果はまだありません。</p>
    @endforelse
</section>

@push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-kfc-feed-expand]');
            if (!btn) return;
            var panelId = btn.getAttribute('data-kfc-feed-panel');
            if (!panelId) return;
            var panel = document.getElementById(panelId);
            if (!panel) return;
            var isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                btn.setAttribute('aria-expanded', 'true');
                btn.textContent = '2位以下を閉じる';
            } else {
                panel.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = '2位以下の釣果を表示';
            }
        });
    </script>
@endpush
