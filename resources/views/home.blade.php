@extends('layouts.app')

@section('title')
{{ $siteTeamName }}
@endsection

@php $heroSeasonYear = $currentSeason?->starts_on?->format('Y') ?? now()->format('Y'); @endphp

@section('hero')
    <section class="kfc-hero-full">
        @if (filled($siteHeroImageUrl))
            <div class="kfc-hero-bg">
                <img src="{{ $siteHeroImageUrl }}" alt="" decoding="async" fetchpriority="high">
            </div>
        @else
            <div class="pointer-events-none absolute -right-24 -top-20 h-80 w-80 rounded-full bg-emerald-400/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 left-10 h-64 w-64 rounded-full bg-amber-400/15 blur-3xl"></div>
        @endif
        <div class="kfc-hero-overlay" aria-hidden="true"></div>
        <div class="kfc-hero-inner">
            <p class="kfc-hero-eyebrow" aria-hidden="true">Bass Fishing Tournament</p>
            <h1 class="kfc-hero-h1">{{ $siteTeamName }}</h1>
            <p class="kfc-hero-lead">{{ $siteHomeTagline }}</p>
            <p><span class="kfc-hero-season" aria-hidden="true">{{ $heroSeasonYear }} Season</span></p>
        </div>
    </section>
@endsection

@section('content')
    <section class="kfc-card kfc-card--bass">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">現在シーズン</h2>
        </div>
            @if ($currentSeason)
                <div class="mt-1 rounded-2xl border border-[color:var(--kfc-border)] bg-gradient-to-r from-emerald-50/80 to-transparent p-4 sm:p-5">
                    <p class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                        <a href="{{ route('seasons.show', $currentSeason) }}" class="kfc-link">{{ $currentSeason->name }}</a>
                    </p>
                    <p class="mt-2">
                        <span class="kfc-date-badge">{{ $currentSeason->starts_on->format('Y/m/d') }} — {{ $currentSeason->ends_on->format('Y/m/d') }}</span>
                    </p>
                    @if (filled($currentSeason->description))
                        <p class="mt-4 text-sm leading-relaxed text-zinc-700">{{ \Illuminate\Support\Str::limit($currentSeason->description, 160) }}</p>
                    @endif
                </div>
            @else
                <p class="kfc-muted mt-3">現在のシーズンが設定されていません。</p>
            @endif
    </section>

    <section class="kfc-card mt-10">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">現在シーズン個人順位（確定試合の累計ポイント）</h2>
        </div>
        @if ($seasonStandings->isEmpty())
            <p class="kfc-muted mt-3">まだポイントが集計されていません（試合確定後に反映）。</p>
        @else
            @include('partials.season_player_standings_table', [
                'standings' => $seasonStandings,
                'seasonCatchStats' => $seasonCatchStats,
                'seasonParticipationStats' => $seasonParticipationStats,
            ])
        @endif
    </section>

    <section class="kfc-card mt-10">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">試合結果一覧</h2>
        </div>
        <ul class="mt-2 flex flex-col gap-3 text-sm">
            @forelse ($recentMatches as $m)
                <li class="kfc-match-event">
                    <div class="min-w-0">
                        <a href="{{ route('matches.show', $m) }}" class="kfc-link text-base font-bold sm:text-lg">{{ $m->title }}</a>
                        <p class="mt-1.5 flex flex-wrap items-center gap-2">
                            <span class="kfc-date-badge">{{ $m->start_datetime->format('Y/m/d H:i') }}</span>
                            @if ($m->field)
                                <span class="text-xs text-zinc-500">{{ $m->field }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2 text-sm text-zinc-600">
                        @if ($m->isBeforeStartDatetime())
                            <span class="kfc-badge-warn">開催前（結果は開催後に表示されます）</span>
                        @elseif (! $m->is_finalized)
                            <span class="kfc-badge-warn">結果は確定後に公開されます</span>
                        @else
                            <a href="{{ route('matches.show', $m) }}#match-standings" class="kfc-btn-emerald text-xs sm:text-sm">試合結果を見る</a>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-6 kfc-muted">試合データがありません。</li>
            @endforelse
        </ul>
    </section>

    @if ($currentSeason)
        @include('partials.season_catch_feed_section', ['seasonCatchMatchBlocks' => $seasonCatchMatchBlocks])
    @endif

    <section class="kfc-card kfc-card--bass mt-10">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">過去シーズン</h2>
        </div>
        @if ($pastSeasons->isEmpty())
            <div class="mt-2 flex flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200/80 bg-emerald-50/30 px-6 py-10 text-center">
                <span class="mb-2 text-3xl" aria-hidden="true">🎣</span>
                <p class="kfc-muted">過去シーズンはまだありません。</p>
            </div>
        @else
            <ul class="mt-2 flex flex-wrap gap-2.5 text-sm">
                @foreach ($pastSeasons as $s)
                    <li>
                        <a href="{{ route('seasons.show', $s) }}" class="inline-flex items-center gap-1.5 rounded-full border border-[color:var(--kfc-border)] bg-white px-4 py-2 font-semibold text-emerald-900 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">{{ $s->name }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>


@endsection
