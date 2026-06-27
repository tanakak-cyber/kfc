@extends('layouts.app')

@section('title', $season->name)

@section('content')
    <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="kfc-muted"><a href="{{ route('seasons.index') }}" class="kfc-link">シーズン一覧</a></p>
            <h1 class="kfc-page-title mt-1">{{ $season->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
        </div>
        @if ($season->is_current)
            <span class="kfc-badge-success self-start">現在シーズン</span>
        @endif
    </div>

    @if ($season->image_path)
        <img
            src="{{ asset('storage/'.$season->image_path) }}"
            alt=""
            class="mb-8 max-h-72 w-full cursor-pointer rounded-2xl object-cover shadow-lg ring-1 ring-zinc-200/80 transition hover:opacity-95"
            role="button"
            tabindex="0"
            onclick="window.kfcOpenImageLightbox({{ json_encode(asset('storage/'.$season->image_path)) }})"
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode(asset('storage/'.$season->image_path)) }});}"
        >
    @endif

    @if ($season->description)
        <div class="kfc-card-sm mb-10 whitespace-pre-line text-sm leading-relaxed text-zinc-700">{{ $season->description }}</div>
    @endif

    <section class="kfc-card mb-10">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">個人順位</h2>
        </div>
        @if ($standings->isEmpty())
            <p class="kfc-muted mt-3">確定済み試合のポイント集計後に表示されます。</p>
        @else
            @include('partials.season_player_standings_table', [
                'standings' => $standings,
                'seasonCatchStats' => $seasonCatchStats,
                'seasonParticipationStats' => $seasonParticipationStats,
            ])
        @endif
    </section>

    <section class="kfc-card">
        <div class="kfc-section-head">
            <h2 class="kfc-section-title">試合結果一覧</h2>
        </div>
        <div class="mt-2 flex flex-col gap-3">
            @forelse ($matches as $m)
                <div class="kfc-match-event">
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
                </div>
            @empty
                <p class="kfc-muted">試合がありません。</p>
            @endforelse
        </div>
    </section>

    @include('partials.season_catch_feed_section', [
        'seasonCatchMatchBlocks' => $seasonCatchMatchBlocks,
        'catchFeedTitle' => 'このシーズンの釣果情報',
    ])
@endsection
