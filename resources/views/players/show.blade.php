@extends('layouts.app')

@section('title', $player->displayLabel())

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        @if ($player->icon)
            @php $iconUrl = asset('storage/'.$player->icon); @endphp
            <img
                src="{{ $iconUrl }}"
                alt=""
                class="h-20 w-20 shrink-0 cursor-pointer rounded-full object-cover ring-2 ring-slate-200 hover:opacity-95"
                role="button"
                tabindex="0"
                onclick="window.kfcOpenImageLightbox({{ json_encode($iconUrl) }})"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($iconUrl) }});}"
            >
        @endif
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $player->displayLabel() }}</h1>
            @if (filled($player->display_name))
                <p class="text-sm text-slate-500">本名: {{ $player->name }}</p>
            @endif
        </div>
    </div>
    <p class="mt-2 text-sm text-slate-600">承認済み釣果に基づく個人成績</p>

    <dl class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs text-slate-500">総釣果数</dt>
            <dd class="text-2xl font-semibold">{{ $totalCatches }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs text-slate-500">最大長さ（cm）</dt>
            <dd class="text-2xl font-semibold">{{ $maxLength ?? '—' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs text-slate-500">最大重さ（kg）</dt>
            <dd class="text-2xl font-semibold">{{ $maxWeight ?? '—' }}</dd>
        </div>
    </dl>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">試合別成績</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">試合</th>
                        <th class="py-2 pr-4">本数</th>
                        <th class="py-2 pr-4">最大長さ</th>
                        <th class="py-2">最大重さ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perMatch as $row)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4">
                                <a href="{{ route('matches.show', $row['match']) }}" class="text-sky-700 hover:underline">{{ $row['match']->title }}</a>
                            </td>
                            <td class="py-2 pr-4">{{ $row['count'] }}</td>
                            <td class="py-2 pr-4">{{ $row['max_length'] }}</td>
                            <td class="py-2">{{ $row['max_weight'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-slate-500">データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
