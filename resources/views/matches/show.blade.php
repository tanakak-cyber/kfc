@extends('layouts.app')

@section('title', $gameMatch->title)

@section('content')
    <p class="kfc-muted">
        <a href="{{ route('seasons.show', $gameMatch->season) }}" class="kfc-link">{{ $gameMatch->season->name }}</a>
    </p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ $gameMatch->held_at->format('Y/m/d H:i') }} · {{ $gameMatch->field }}</p>
    @if ($gameMatch->launch_shop)
        <p class="text-sm text-zinc-600">出艇: {{ $gameMatch->launch_shop }}</p>
    @endif
    <div class="mt-4 flex flex-wrap gap-2">
        @php
            $statusLabel = match ($gameMatch->status) {
                \App\Enums\MatchStatus::Scheduled => '予定',
                \App\Enums\MatchStatus::InProgress => '開催中',
                \App\Enums\MatchStatus::Completed => '完了',
            };
        @endphp
        <span class="kfc-badge">ステータス: {{ $statusLabel }}</span>
        @if ($gameMatch->is_finalized)
            <span class="kfc-badge-success">結果確定</span>
        @endif
    </div>

    @if ($gameMatch->rules)
        <div class="kfc-card-sm mt-8 whitespace-pre-line text-sm leading-relaxed text-zinc-700">
            <h2 class="kfc-section-title">ルール・概要</h2>
            <p class="mt-3">{{ $gameMatch->rules }}</p>
        </div>
    @endif

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">参加チーム</h2>
        <ul class="mt-4 space-y-3 text-sm">
            @foreach ($gameMatch->teams as $team)
                <li class="kfc-card-nested !p-4">
                    <p class="font-semibold text-zinc-900">{{ $team->name }}</p>
                    <p class="mt-1 text-zinc-600">
                        @foreach ($team->players as $p)
                            <a href="{{ route('players.show', $p) }}" class="kfc-link">{{ $p->displayLabel() }}</a>@if (! $loop->last) <span class="text-zinc-400">/</span> @endif
                        @endforeach
                    </p>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">順位表（承認済み釣果・上位3本合計）</h2>
        <div class="kfc-table-shell mt-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="kfc-thead">
                    <tr>
                        <th class="px-4 py-3">順位</th>
                        <th class="px-4 py-3">チーム</th>
                        <th class="px-4 py-3">合計 kg</th>
                        <th class="px-4 py-3">ビッグ kg</th>
                        <th class="px-4 py-3">ポイント</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gameMatch->matchResults->sortBy('rank') as $r)
                        <tr class="kfc-trow">
                            <td class="px-4 py-3 font-semibold">{{ $r->rank }}</td>
                            <td class="px-4 py-3">{{ $r->team->name }}</td>
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
                        <p class="mt-1 text-zinc-600">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_kg }} kg</p>
                    </div>
                </div>
            @empty
                <p class="kfc-muted sm:col-span-2">承認済みの釣果はまだありません。</p>
            @endforelse
        </div>
    </section>
@endsection
