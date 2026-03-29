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
        @if (! $gameMatch->is_finalized)
            <div class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50/60 px-4 py-3 text-sm text-amber-950">
                <p class="font-semibold text-amber-900">この試合は未確定です</p>
                <p class="mt-1 leading-relaxed text-amber-950/90">
                    トップページ・シーズン詳細の「個人順位」は<strong>確定済み試合だけ</strong>を集計しています。ここに表示されている順位・ポイントは、確定前でも再計算結果として表示されますが、シーズン合計にはまだ含まれません。
                </p>
            </div>
        @else
            <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                シーズンの個人順位は、同じルール（チーム戦はメンバー全員にチーム順位分＋個人の追加ポイント）で<strong>確定済み試合</strong>を合算した値です。
            </p>
        @endif
        @if ($gameMatch->isTeamMatch())
            <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                下の表の「ポイント」は<strong>チーム単位の順位ポイント</strong>です。各選手の「この試合での合計」は、その下の「選手別の付与」表で確認できます（個人戦と同様に順位分＋追加ポイント）。
            </p>
        @endif
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
                            <td class="px-4 py-3 tabular-nums font-medium">
                                @if ($gameMatch->isIndividualMatch())
                                    @php $extra = (int) ($playerBonusTotals->get($r->player_id) ?? 0); @endphp
                                    @if ($extra > 0)
                                        <span class="font-semibold text-zinc-900">{{ $r->points + $extra }}</span>
                                        <span class="mt-0.5 block text-xs font-normal text-zinc-500">順位 {{ $r->points }} + 追加 {{ $extra }}</span>
                                    @else
                                        {{ $r->points }}
                                    @endif
                                @else
                                    {{ $r->points }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 kfc-muted">順位データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($gameMatch->isTeamMatch() && $teamMatchPlayerBreakdown->isNotEmpty())
            <div class="mt-8">
                <h3 class="text-base font-semibold text-zinc-900">選手別の付与（この試合）</h3>
                <p class="mt-1 text-sm text-zinc-600">
                    チーム順位ポイントはメンバー全員に同じ点数が付きます。追加ポイントがある選手は個人に上乗せされます。シーズン個人順位の集計ロジックと同じです。
                </p>
                <div class="kfc-table-shell mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="kfc-thead">
                            <tr>
                                <th class="px-4 py-3">チーム</th>
                                <th class="px-4 py-3">選手</th>
                                <th class="px-4 py-3">順位分（チーム）</th>
                                <th class="px-4 py-3">追加</th>
                                <th class="px-4 py-3">この試合での合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($teamMatchPlayerBreakdown as $row)
                                <tr class="kfc-trow">
                                    <td class="px-4 py-3">{{ $row['team_name'] }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('players.show', $row['player']) }}" class="kfc-link">{{ $row['player']->displayLabel() }}</a>
                                    </td>
                                    <td class="px-4 py-3 tabular-nums">{{ $row['rank_points'] }}</td>
                                    <td class="px-4 py-3 tabular-nums">
                                        @if ($row['bonus'] > 0)
                                            +{{ $row['bonus'] }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 tabular-nums font-medium">{{ $row['match_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        @if ($gameMatch->matchPlayerBonusPoints->isNotEmpty())
            <div class="mt-6 rounded-xl border border-emerald-200/70 bg-emerald-50/40 px-4 py-3 text-sm text-zinc-800">
                <p class="font-semibold text-emerald-900">選手への追加ポイント</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-zinc-700">
                    @foreach ($gameMatch->matchPlayerBonusPoints->sortBy(fn ($b) => $b->player->name ?? '') as $b)
                        <li>
                            <span class="font-medium">{{ $b->player->displayLabel() }}</span>
                            <span class="tabular-nums">+{{ $b->points }}pt</span>
                            @if (filled($b->reason))
                                <span class="text-zinc-600">（{{ $b->reason }}）</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
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
