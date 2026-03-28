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
        <h2 class="kfc-section-title">個人順位</h2>
        @if ($standings->isEmpty())
            <p class="kfc-muted mt-3">確定済み試合のポイント集計後に表示されます。</p>
        @else
            @include('partials.season_player_standings_table', [
                'standings' => $standings,
                'seasonCatchStats' => $seasonCatchStats,
            ])
        @endif
    </section>

    <section class="kfc-card">
        <h2 class="kfc-section-title">試合一覧</h2>
        <div class="mt-6 space-y-3">
            @forelse ($matches as $m)
                <div class="kfc-card-nested">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <a href="{{ route('matches.show', $m) }}" class="kfc-link text-base">{{ $m->title }}</a>
                            <p class="mt-0.5 text-sm text-zinc-600">{{ $m->held_at->format('Y/m/d H:i') }} · {{ $m->field }}</p>
                        </div>
                        <a href="{{ route('matches.show', $m) }}" class="kfc-link-subtle shrink-0 text-sm">詳細 →</a>
                    </div>
                    @php
                        $topThree = $m->matchResults->sortBy('rank')->take(3);
                    @endphp
                    <p class="mt-3 text-sm text-zinc-700">
                        <span class="font-semibold text-zinc-800">上位:</span>
                        @if ($topThree->isEmpty())
                            —
                        @else
                            {{ $topThree->map(fn ($r) => $r->team->name.'（'.$r->rank.'位）')->implode('、') }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="kfc-muted">試合がありません。</p>
            @endforelse
        </div>
    </section>
@endsection
