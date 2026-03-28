@extends('layouts.app')

@section('title', $season->name)

@section('content')
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500"><a href="{{ route('seasons.index') }}" class="text-sky-700 hover:underline">シーズン一覧</a></p>
            <h1 class="text-2xl font-bold text-slate-900">{{ $season->name }}</h1>
            <p class="text-sm text-slate-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
        </div>
        @if ($season->is_current)
            <span class="self-start rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">現在シーズン</span>
        @endif
    </div>

    @if ($season->image_path)
        <img
            src="{{ asset('storage/'.$season->image_path) }}"
            alt=""
            class="mb-6 max-h-64 w-full cursor-pointer rounded-xl object-cover hover:opacity-95"
            role="button"
            tabindex="0"
            onclick="window.kfcOpenImageLightbox({{ json_encode(asset('storage/'.$season->image_path)) }})"
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode(asset('storage/'.$season->image_path)) }});}"
        >
    @endif

    @if ($season->description)
        <div class="mb-8 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm whitespace-pre-line">{{ $season->description }}</div>
    @endif

    <section class="mb-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">個人順位</h2>
        @if ($standings->isEmpty())
            <p class="mt-2 text-sm text-slate-500">確定済み試合のポイント集計後に表示されます。</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">順位</th>
                            <th class="py-2 pr-4">プレイヤー</th>
                            <th class="py-2">合計ポイント</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($standings as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4">{{ $row->display_rank }}</td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('players.show', $row->player) }}" class="font-medium text-sky-700 hover:underline">{{ $row->player->displayLabel() }}</a>
                                </td>
                                <td class="py-2">{{ $row->total_points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">試合一覧</h2>
        <div class="mt-4 space-y-4">
            @forelse ($matches as $m)
                <div class="rounded-lg border border-slate-100 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <a href="{{ route('matches.show', $m) }}" class="text-base font-semibold text-sky-700 hover:underline">{{ $m->title }}</a>
                            <p class="text-sm text-slate-600">{{ $m->held_at->format('Y/m/d H:i') }} · {{ $m->field }}</p>
                        </div>
                        <a href="{{ route('matches.show', $m) }}" class="text-sm text-sky-600 hover:underline">詳細</a>
                    </div>
                    @php
                        $topThree = $m->matchResults->sortBy('rank')->take(3);
                    @endphp
                    <p class="mt-2 text-sm text-slate-700">
                        <span class="font-medium">上位:</span>
                        @if ($topThree->isEmpty())
                            —
                        @else
                            {{ $topThree->map(fn ($r) => $r->team->name.'（'.$r->rank.'位）')->implode('、') }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">試合がありません。</p>
            @endforelse
        </div>
    </section>
@endsection
