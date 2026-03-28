@extends('layouts.app')

@section('title', $gameMatch->title)

@section('content')
    <p class="kfc-muted">
        <a href="{{ route('seasons.show', $gameMatch->season) }}" class="kfc-link">{{ $gameMatch->season->name }}</a>
    </p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }}</h1>
    <p class="mt-2 text-sm text-zinc-600">
        開始 {{ $gameMatch->start_datetime->format('Y/m/d H:i') }}
        @if ($gameMatch->end_datetime)
            〜 終了 {{ $gameMatch->end_datetime->format('Y/m/d H:i') }}
        @endif
        · {{ $gameMatch->field }}
    </p>
    @if ($gameMatch->launch_shop)
        <p class="text-sm text-zinc-600">出艇: {{ $gameMatch->launch_shop }}</p>
    @endif
    <div class="mt-4 flex flex-wrap gap-2">
        <span class="kfc-badge">形式: {{ $gameMatch->match_type->label() }}</span>
        <span class="kfc-badge">ステータス: {{ $gameMatch->status->label() }}</span>
    </div>

    @if ($gameMatch->rules)
        <div class="kfc-card-sm mt-8 whitespace-pre-line text-sm leading-relaxed text-zinc-700">
            <h2 class="kfc-section-title">ルール・概要</h2>
            <p class="mt-3">{{ $gameMatch->rules }}</p>
        </div>
    @endif

    @if ($gameMatch->isTeamMatch())
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">参加チーム</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($gameMatch->teams as $team)
                    <li class="kfc-card-nested !p-4">
                        <p class="font-semibold text-zinc-900">{{ $team->name }}</p>
                        <p class="mt-1 text-zinc-600">
                            @foreach ($team->players as $p)
                                <a href="{{ route('players.show', $p) }}" class="kfc-link">{{ $p->displayLabel() }}</a>@if (! $loop->last) <span class="text-zinc-400">/</span> @endif
                            @endforeach
                        </p>
                    </li>
                @empty
                    <li class="kfc-muted">登録チームがありません。</li>
                @endforelse
            </ul>
        </section>
    @else
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">参加者</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($gameMatch->matchParticipants->sortBy(fn ($p) => $p->player->name ?? '') as $mp)
                    <li class="kfc-card-nested !p-4">
                        <p class="font-semibold text-zinc-900">
                            <a href="{{ route('players.show', $mp->player) }}" class="kfc-link">{{ $mp->player->displayLabel() }}</a>
                        </p>
                        <p class="mt-1 text-zinc-600">
                            @if ($mp->is_present)
                                <span class="kfc-badge-success">出席</span>
                            @else
                                <span class="kfc-badge-warn">欠席</span>
                            @endif
                        </p>
                    </li>
                @empty
                    <li class="kfc-muted">参加者が登録されていません。</li>
                @endforelse
            </ul>
        </section>
    @endif

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">順位表（承認済み釣果・上位3本合計）</h2>
        <div class="kfc-table-shell mt-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="kfc-thead">
                    <tr>
                        <th class="px-4 py-3">順位</th>
                        @if ($gameMatch->isTeamMatch())
                            <th class="px-4 py-3">チーム</th>
                        @else
                            <th class="px-4 py-3">プレイヤー</th>
                        @endif
                        <th class="px-4 py-3">合計（g）</th>
                        <th class="px-4 py-3">ビッグ（g）</th>
                        <th class="px-4 py-3">ポイント</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gameMatch->matchResults->sortBy('rank') as $r)
                        <tr class="kfc-trow">
                            <td class="px-4 py-3 font-semibold">{{ $r->rank }}</td>
                            <td class="px-4 py-3">
                                @if ($gameMatch->isTeamMatch())
                                    {{ $r->team?->name ?? '—' }}
                                @else
                                    {{ $r->player?->displayLabel() ?? '—' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $r->total_weight }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $r->big_fish_weight }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium">{{ $r->points }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 kfc-muted">順位データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">釣果（承認済み）</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @forelse ($catches as $catch)
                @php
                    $catchUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
                @endphp
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-md shadow-zinc-950/5 ring-1 ring-zinc-950/[0.03]">
                    @include('partials.catch_image_slider', ['urls' => $catchUrls, 'sliderId' => 'catch-'.$catch->id, 'roundedTop' => true])
                    <div class="border-t border-zinc-100 p-4 text-sm">
                        <p class="font-semibold text-zinc-900">{{ $catch->player->displayLabel() }}</p>
                        @if ($catch->team)
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $catch->team->name }}</p>
                        @endif
                        <p class="mt-1 text-zinc-600">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_g }} g</p>
                    </div>
                </div>
            @empty
                <p class="kfc-muted sm:col-span-2">承認済みの釣果はまだありません。</p>
            @endforelse
        </div>
    </section>
@endsection
