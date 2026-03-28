@extends('layouts.admin')

@section('title', '試合編集')

@section('content')
    <h1 class="text-2xl font-bold">試合編集</h1>
    <form method="post" action="{{ route('admin.matches.update', $gameMatch) }}" class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('put')
        @include('admin.matches._form', ['gameMatch' => $gameMatch, 'seasons' => $seasons, 'selectedSeasonId' => $gameMatch->season_id])
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">更新</button>
    </form>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">結果確定</h2>
        <p class="mt-2 text-sm text-slate-600">確定すると釣果の承認・却下・エントリー変更はできなくなります。シーズン累計ポイントを再計算します。</p>
        @if ($gameMatch->is_finalized)
            <p class="mt-3 text-sm font-medium text-emerald-700">すでに確定済みです。</p>
        @else
            <form method="post" action="{{ route('admin.matches.finalize', $gameMatch) }}" class="mt-4" onsubmit="return confirm('試合を確定しますか？');">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">この試合を確定する</button>
            </form>
        @endif
    </div>

    <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50/50 p-6 shadow-sm">
        <h2 class="text-lg font-semibold">試合の順位・ポイントを再計算</h2>
        <p class="mt-2 text-sm text-slate-700">
            この試合の <code class="rounded bg-white px-1 text-xs ring-1 ring-slate-200">match_results</code> を、いまのルール（承認済み釣果0件のチームは<strong>1ポイント</strong>など）で<strong>上書き</strong>し、そのあとシーズン個人順位も更新します。
        </p>
        <p class="mt-2 text-sm text-slate-600">確定後にルールが変わったときや、DBに古いポイントが残っているときに押してください。</p>
        <form method="post" action="{{ route('admin.matches.resync-match-results-and-season', $gameMatch) }}" class="mt-4" onsubmit="return confirm('この試合の match_results を再計算し、シーズン個人順位も更新しますか？');">
            @csrf
            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                この試合を再計算して個人順位も更新
            </button>
        </form>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">シーズン個人順位の再集計のみ</h2>
        <p class="mt-2 text-sm text-slate-600">
            所属シーズン「<span class="font-medium text-slate-800">{{ $gameMatch->season->name }}</span>」の個人ポイント（<code class="rounded bg-slate-100 px-1 text-xs">season_player_points</code>）を、
            そのシーズン内の<strong>すべての確定済み試合</strong>の <code class="rounded bg-slate-100 px-1 text-xs">match_results</code> から作り直します（<strong>match_results 自体は変えません</strong>）。
        </p>
        <p class="mt-2 text-sm text-slate-500">ポイントのルール修正後は、上の「試合の順位・ポイントを再計算」を先に実行してください。</p>
        <form method="post" action="{{ route('admin.matches.recalculate-season-player-points', $gameMatch) }}" class="mt-4" onsubmit="return confirm('このシーズンの個人順位を再集計しますか？');">
            @csrf
            <button type="submit" class="rounded-lg border border-sky-300 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-900 hover:bg-sky-100">
                個人順位を再集計する
            </button>
        </form>
    </div>
@endsection
