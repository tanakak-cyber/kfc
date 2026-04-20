{{-- 期待: $seasonCatchMatchBlocks（試合×順位の入れ子）、任意 $catchFeedTitle --}}
@php
    $catchFeedTitle = $catchFeedTitle ?? '今シーズンの釣果情報';
@endphp
<section class="kfc-card mt-10">
    <h2 class="kfc-section-title">{{ $catchFeedTitle }}</h2>

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
        <div class="mt-10 min-w-0 border-t border-zinc-100 pt-10 first:mt-6 first:border-0 first:pt-0">
            <h3 class="kfc-subsection-title">
                <a href="{{ route('matches.show', $match) }}" class="kfc-link-subtle hover:text-emerald-800">{{ $match->title }}</a>
            </h3>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $match->start_datetime->format('Y/m/d H:i') }}
                @if ($match->field)
                    <span class="text-zinc-400"> · </span>{{ $match->field }}
                @endif
            </p>

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
