@extends('layouts.app')

@section('title', 'トップ')

@section('content')
    <section class="relative mb-12 overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-zinc-900 via-zinc-800 to-emerald-950 px-6 py-14 text-white shadow-2xl shadow-zinc-900/40 ring-1 ring-white/10 sm:px-10">
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 left-10 h-48 w-48 rounded-full bg-teal-500/15 blur-3xl"></div>
        <div class="relative">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300/90">ブラックバス釣り大会</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $siteTeamName }}</h1>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-zinc-300 sm:text-base">{{ $siteHomeTagline }}</p>
        </div>
    </section>

    <section class="kfc-card mt-10">
        <div class="border-b border-zinc-100 pb-6">
            <h2 class="kfc-section-title">現在シーズン</h2>
            @if ($currentSeason)
                <p class="mt-3 text-2xl font-bold tracking-tight">
                    <a href="{{ route('seasons.show', $currentSeason) }}" class="kfc-link">{{ $currentSeason->name }}</a>
                </p>
                <p class="mt-1 text-sm text-zinc-600">{{ $currentSeason->starts_on->format('Y/m/d') }} — {{ $currentSeason->ends_on->format('Y/m/d') }}</p>
                @if (filled($currentSeason->description))
                    <p class="mt-4 text-sm leading-relaxed text-zinc-700">{{ \Illuminate\Support\Str::limit($currentSeason->description, 160) }}</p>
                @endif
            @else
                <p class="kfc-muted mt-3">現在のシーズンが設定されていません。</p>
            @endif
        </div>

        <h2 class="kfc-section-title mt-8">現在シーズン個人順位（確定試合の累計ポイント）</h2>
        @if ($seasonStandings->isEmpty())
            <p class="kfc-muted mt-3">まだポイントが集計されていません（試合確定後に反映）。</p>
        @else
            @include('partials.season_player_standings_table', [
                'standings' => $seasonStandings,
                'seasonCatchStats' => $seasonCatchStats,
            ])
        @endif
    </section>

    <section class="kfc-card mt-10">
        <h2 class="kfc-section-title">試合結果一覧</h2>
        <ul class="mt-2 divide-y divide-zinc-100 text-sm">
            @forelse ($recentMatches as $m)
                <li class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <a href="{{ route('matches.show', $m) }}" class="kfc-link text-base">{{ $m->title }}</a>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $m->start_datetime->format('Y/m/d H:i') }} · {{ $m->field }}</p>
                    </div>
                    <div class="text-sm text-zinc-600">
                        @php $top = $m->matchResults->sortBy('rank')->first(); @endphp
                        @if ($top && $m->isTeamMatch() && $top->team)
                            <span class="kfc-badge">首位: {{ $top->team->name }}（{{ $top->total_weight }} kg）</span>
                        @elseif ($top && $m->isIndividualMatch() && $top->player)
                            <span class="kfc-badge">首位: {{ $top->player->displayLabel() }}（{{ $top->total_weight }} kg）</span>
                        @else
                            <span class="kfc-badge-warn">順位未確定</span>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-6 kfc-muted">試合データがありません。</li>
            @endforelse
        </ul>
    </section>

    @if ($currentSeason)
        @include('partials.season_catch_feed_section', ['seasonCatchFeed' => $seasonCatchFeed])
    @endif

    <section class="kfc-card mt-10">
        <h2 class="kfc-section-title">過去シーズン</h2>
        <ul class="mt-4 flex flex-wrap gap-2 text-sm">
            @forelse ($pastSeasons as $s)
                <li>
                    <a href="{{ route('seasons.show', $s) }}" class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 font-medium text-zinc-800 transition hover:border-emerald-200 hover:bg-emerald-50/80 hover:text-emerald-800">{{ $s->name }}</a>
                </li>
            @empty
                <li class="kfc-muted">過去シーズンはまだありません。</li>
            @endforelse
        </ul>
    </section>
@endsection
