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
