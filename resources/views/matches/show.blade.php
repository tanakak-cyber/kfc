@extends('layouts.app')

@section('title', $gameMatch->title)

@section('content')
    <p class="text-sm text-slate-500">
        <a href="{{ route('seasons.show', $gameMatch->season) }}" class="text-sky-700 hover:underline">{{ $gameMatch->season->name }}</a>
    </p>
    <h1 class="text-2xl font-bold text-slate-900">{{ $gameMatch->title }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $gameMatch->held_at->format('Y/m/d H:i') }} · {{ $gameMatch->field }}</p>
    @if ($gameMatch->launch_shop)
        <p class="text-sm text-slate-600">出艇: {{ $gameMatch->launch_shop }}</p>
    @endif
    <div class="mt-2 flex flex-wrap gap-2 text-xs">
        @php
            $statusLabel = match ($gameMatch->status) {
                \App\Enums\MatchStatus::Scheduled => '予定',
                \App\Enums\MatchStatus::InProgress => '開催中',
                \App\Enums\MatchStatus::Completed => '完了',
            };
        @endphp
        <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">ステータス: {{ $statusLabel }}</span>
        @if ($gameMatch->is_finalized)
            <span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-800">結果確定</span>
        @endif
    </div>

    @if ($gameMatch->rules)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm whitespace-pre-line">
            <h2 class="font-semibold text-slate-900">ルール・概要</h2>
            <p class="mt-2">{{ $gameMatch->rules }}</p>
        </div>
    @endif

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">参加チーム</h2>
        <ul class="mt-3 space-y-3 text-sm">
            @foreach ($gameMatch->teams as $team)
                <li class="rounded-lg border border-slate-100 p-3">
                    <p class="font-medium">{{ $team->name }}</p>
                    <p class="text-slate-600">
                        @foreach ($team->players as $p)
                            <a href="{{ route('players.show', $p) }}" class="text-sky-700 hover:underline">{{ $p->displayLabel() }}</a>@if (! $loop->last) / @endif
                        @endforeach
                    </p>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">順位表（承認済み釣果・上位3本合計）</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">順位</th>
                        <th class="py-2 pr-4">チーム</th>
                        <th class="py-2 pr-4">合計 kg</th>
                        <th class="py-2 pr-4">ビッグ kg</th>
                        <th class="py-2">ポイント</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gameMatch->matchResults->sortBy('rank') as $r)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4">{{ $r->rank }}</td>
                            <td class="py-2 pr-4">{{ $r->team->name }}</td>
                            <td class="py-2 pr-4">{{ $r->total_weight }}</td>
                            <td class="py-2 pr-4">{{ $r->big_fish_weight }}</td>
                            <td class="py-2">{{ $r->points }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-slate-500">順位データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">釣果（承認済み）</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @forelse ($catches as $catch)
                <div class="overflow-hidden rounded-lg border border-slate-100">
                    @php $img = $catch->images->first(); @endphp
                    @if ($img)
                        @php $catchImgUrl = asset('storage/'.$img->path); @endphp
                        <img
                            src="{{ $catchImgUrl }}"
                            alt=""
                            class="aspect-video w-full cursor-pointer object-cover hover:opacity-95"
                            role="button"
                            tabindex="0"
                            onclick="window.kfcOpenImageLightbox({{ json_encode($catchImgUrl) }})"
                            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($catchImgUrl) }});}"
                        >
                    @endif
                    <div class="p-3 text-sm">
                        <p class="font-medium">{{ $catch->player->displayLabel() }}</p>
                        <p class="text-slate-600">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_kg }} kg</p>
                    </div>
                </div>
            @empty
                <p class="text-slate-500">承認済みの釣果はまだありません。</p>
            @endforelse
        </div>
    </section>
@endsection
