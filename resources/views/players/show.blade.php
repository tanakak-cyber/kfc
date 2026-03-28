@extends('layouts.app')

@section('title', $player->displayLabel())

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        @if ($player->icon)
            @php $iconUrl = asset('storage/'.$player->icon); @endphp
            <img
                src="{{ $iconUrl }}"
                alt=""
                class="h-24 w-24 shrink-0 cursor-pointer rounded-2xl object-cover shadow-lg ring-2 ring-zinc-200/80 transition hover:opacity-95"
                role="button"
                tabindex="0"
                onclick="window.kfcOpenImageLightbox({{ json_encode($iconUrl) }})"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($iconUrl) }});}"
            >
        @endif
        <div>
            <h1 class="kfc-page-title">{{ $player->displayLabel() }}</h1>
            @if (filled($player->display_name))
                <p class="kfc-muted mt-1">本名: {{ $player->name }}</p>
            @endif
        </div>
    </div>
    <p class="mt-3 text-sm text-zinc-600">承認済み釣果に基づく個人成績</p>

    <dl class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="kfc-card-sm text-center sm:text-left">
            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">総釣果数</dt>
            <dd class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">{{ $totalCatches }}</dd>
        </div>
        <div class="kfc-card-sm text-center sm:text-left">
            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">最大長さ（cm）</dt>
            <dd class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">{{ $maxLength ?? '—' }}</dd>
        </div>
        <div class="kfc-card-sm text-center sm:text-left">
            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">最大重さ（kg）</dt>
            <dd class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">{{ $maxWeight ?? '—' }}</dd>
        </div>
    </dl>

    <section class="kfc-card mt-10">
        <h2 class="kfc-section-title">釣果（承認済み）</h2>
        <p class="kfc-muted mt-2">試合ごとの投稿です。写真は横にスワイプして切り替えられます。</p>
        @if ($playerCatches->isEmpty())
            <p class="kfc-muted mt-6">まだ承認済みの釣果がありません。</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                @foreach ($playerCatches as $catch)
                    @php
                        $catchUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-md shadow-zinc-950/5 ring-1 ring-zinc-950/[0.03]">
                        @include('partials.catch_image_slider', ['urls' => $catchUrls, 'sliderId' => 'catch-'.$catch->id, 'roundedTop' => true])
                        <div class="space-y-1 border-t border-zinc-100 p-4 text-sm">
                            <p>
                                <a href="{{ route('matches.show', $catch->gameMatch) }}" class="kfc-link font-semibold">{{ $catch->gameMatch->title }}</a>
                            </p>
                            <p class="text-xs text-zinc-500">
                                {{ $catch->gameMatch->start_datetime->format('Y/m/d H:i') }}
                                @if ($catch->gameMatch->season)
                                    · {{ $catch->gameMatch->season->name }}
                                @endif
                                @if ($catch->team)
                                    · {{ $catch->team->name }}
                                @else
                                    · 個人戦
                                @endif
                            </p>
                            <p class="mt-2 text-zinc-700">長さ {{ $catch->length_cm }} cm / 重さ {{ $catch->weight_kg }} kg</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="kfc-card mt-10">
        <h2 class="kfc-section-title">試合別成績</h2>
        <div class="kfc-table-shell mt-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="kfc-thead">
                    <tr>
                        <th class="px-4 py-3">試合</th>
                        <th class="px-4 py-3">本数</th>
                        <th class="px-4 py-3">最大長さ</th>
                        <th class="px-4 py-3">最大重さ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perMatch as $row)
                        <tr class="kfc-trow">
                            <td class="px-4 py-3">
                                <a href="{{ route('matches.show', $row['match']) }}" class="kfc-link">{{ $row['match']->title }}</a>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $row['count'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $row['max_length'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $row['max_weight'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 kfc-muted">データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
