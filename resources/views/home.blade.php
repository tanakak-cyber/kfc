@extends('layouts.app')

@section('title', 'トップ')

@section('content')
    <section class="mb-10 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 px-6 py-12 text-white shadow-lg">
        <p class="text-sm uppercase tracking-widest text-slate-300">ブラックバス釣り大会</p>
        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ config('app.name') }}</h1>
        <p class="mt-3 max-w-xl text-sm text-slate-200">シーズン・試合・チーム・釣果を一元管理。ランキングは合計重量（上位3本）で決定します。</p>
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">チーム概要</h2>
            <p class="mt-2 text-sm text-slate-600">2名1組のチームは試合ごとに登録されます。</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse ($teamsPreview as $team)
                    <li class="flex flex-col gap-0.5 border-b border-slate-100 py-2 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="font-medium">{{ $team->name }}</span>
                            @if ($team->gameMatch)
                                <p class="text-xs text-slate-500">{{ $team->gameMatch->title }}</p>
                            @endif
                        </div>
                        <span class="text-slate-500">{{ $team->players_count }}名</span>
                    </li>
                @empty
                    <li class="text-slate-500">登録チームはまだありません。</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">現在シーズン</h2>
            @if ($currentSeason)
                <p class="mt-2 text-xl font-bold text-slate-900">{{ $currentSeason->name }}</p>
                <p class="text-sm text-slate-600">{{ $currentSeason->starts_on->format('Y/m/d') }} — {{ $currentSeason->ends_on->format('Y/m/d') }}</p>
                <p class="mt-3 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($currentSeason->description, 160) }}</p>
            @else
                <p class="mt-2 text-sm text-slate-500">現在のシーズンが設定されていません。</p>
            @endif
        </section>
    </div>

    <section class="mt-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">現在シーズン個人順位（確定試合の累計ポイント）</h2>
        @if ($seasonStandings->isEmpty())
            <p class="mt-3 text-sm text-slate-500">まだポイントが集計されていません（試合確定後に反映）。</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">順位</th>
                            <th class="py-2 pr-4">プレイヤー</th>
                            <th class="py-2">合計ポイント</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seasonStandings as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4 font-medium">{{ $row->display_rank }}</td>
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

    <section class="mt-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">試合結果一覧</h2>
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @forelse ($recentMatches as $m)
                <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <a href="{{ route('matches.show', $m) }}" class="font-semibold text-sky-700 hover:underline">{{ $m->title }}</a>
                        <p class="text-slate-500">{{ $m->held_at->format('Y/m/d H:i') }} · {{ $m->field }}</p>
                    </div>
                    <div class="text-slate-600">
                        @php $top = $m->matchResults->first(); @endphp
                        @if ($top && $top->team)
                            <span>首位: {{ $top->team->name }}（{{ $top->total_weight }} kg）</span>
                        @else
                            <span>順位未確定</span>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-4 text-slate-500">試合データがありません。</li>
            @endforelse
        </ul>
    </section>

    <section class="mt-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">過去シーズン</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($pastSeasons as $s)
                <li>
                    <a href="{{ route('seasons.show', $s) }}" class="text-sky-700 hover:underline">{{ $s->name }}</a>
                </li>
            @empty
                <li class="text-slate-500">過去シーズンはまだありません。</li>
            @endforelse
        </ul>
    </section>
@endsection
