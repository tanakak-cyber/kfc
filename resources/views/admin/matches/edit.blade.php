@extends('layouts.admin')

@section('title', '試合編集')

@section('content')
    <h1 class="kfc-page-title">試合編集</h1>
    <p class="kfc-muted mt-2">
        ステータス: <span class="font-semibold text-zinc-800">{{ $gameMatch->status->label() }}</span>
        <span class="text-zinc-400">·</span>
        開始 {{ $gameMatch->start_datetime->format('Y/m/d H:i') }}
        @if ($gameMatch->end_datetime)
            <span class="text-zinc-400">·</span> 終了 {{ $gameMatch->end_datetime->format('Y/m/d H:i') }}
        @endif
    </p>
    <p class="kfc-muted mt-1">形式: <span class="font-semibold text-zinc-800">{{ $gameMatch->match_type->label() }}</span>
        @if ($gameMatch->isTeamMatch())
            — <a href="{{ route('admin.matches.teams.index', $gameMatch) }}" class="kfc-link">チーム・投稿URL</a>
        @else
            — <a href="{{ route('admin.matches.participants.index', $gameMatch) }}" class="kfc-link">参加者・投稿URL</a>
        @endif
    </p>
    <form method="post" action="{{ route('admin.matches.update', $gameMatch) }}" class="kfc-card mt-8 space-y-5">
        @csrf
        @method('put')
        @include('admin.matches._form', ['gameMatch' => $gameMatch, 'seasons' => $seasons, 'selectedSeasonId' => $gameMatch->season_id])
        <button type="submit" class="kfc-btn-primary">更新</button>
    </form>

    <div class="kfc-card mt-8 border-red-200/60 bg-red-50/20 ring-red-500/5">
        <h2 class="kfc-section-title text-red-900">試合を削除</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-700">
            紐づくチーム・参加者・釣果・順位データをすべて削除します（釣果画像ファイルも削除）。<strong>未確定の試合のみ</strong>削除できます。
        </p>
        @if ($gameMatch->is_finalized)
            <p class="mt-4 text-sm text-red-800">確定済みのため削除できません。先に「確定を解除」してください。</p>
        @else
            <form
                method="post"
                action="{{ route('admin.matches.destroy', $gameMatch) }}"
                class="mt-5"
                onsubmit="return confirm('この試合を完全に削除しますか？\n\nこの操作は取り消せません。');"
            >
                @csrf
                @method('delete')
                <button type="submit" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 shadow-sm transition hover:bg-red-50">
                    この試合を削除する
                </button>
            </form>
        @endif
    </div>

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">結果確定</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-600">確定すると釣果の承認・却下・エントリー変更はできなくなります。シーズン累計ポイントを再計算します。</p>
        @if ($gameMatch->is_finalized)
            <p class="mt-4 text-sm font-semibold text-emerald-700">確定済みです。</p>
            <p class="mt-2 text-sm text-zinc-600">誤って確定した場合は「確定を解除」できます。解除後は試合の編集・チーム追加・釣果の承認などが再び可能になり、この試合のポイントはシーズン集計から外して再計算されます（<code class="rounded bg-zinc-100 px-1 text-xs">match_results</code> のデータ自体は消えません）。</p>
            <form method="post" action="{{ route('admin.matches.unfinalize', $gameMatch) }}" class="mt-5" onsubmit="return confirm('試合の確定を解除しますか？\n\n・釣果の承認・チーム編集・試合情報の編集が再び可能になります。\n・シーズン個人ポイントからこの試合の分が除かれ、再集計されます。');">
                @csrf
                <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-800 shadow-sm transition hover:bg-red-100">確定を解除する</button>
            </form>
        @else
            <form method="post" action="{{ route('admin.matches.finalize', $gameMatch) }}" class="mt-5" onsubmit="return confirm('試合を確定しますか？');">
                @csrf
                <button type="submit" class="kfc-btn-emerald">この試合を確定する</button>
            </form>
        @endif
    </div>

    <div class="kfc-card mt-8 border-amber-200/60 bg-amber-50/30 ring-amber-500/5">
        <h2 class="kfc-section-title">試合の順位・ポイントを再計算</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-700">
            この試合の <code class="rounded-md bg-white px-1.5 py-0.5 text-xs font-mono ring-1 ring-zinc-200">match_results</code> を、いまのルール（承認済み釣果0件のチームは<strong>1ポイント</strong>など）で<strong>上書き</strong>し、そのあとシーズン個人順位も更新します。
        </p>
        <p class="mt-2 text-sm text-zinc-600">確定後にルールが変わったときや、DBに古いポイントが残っているときに押してください。</p>
        <form method="post" action="{{ route('admin.matches.resync-match-results-and-season', $gameMatch) }}" class="mt-5" onsubmit="return confirm('この試合の match_results を再計算し、シーズン個人順位も更新しますか？');">
            @csrf
            <button type="submit" class="kfc-btn-amber">
                この試合を再計算して個人順位も更新
            </button>
        </form>
    </div>

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">シーズン個人順位の再集計のみ</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-600">
            所属シーズン「<span class="font-semibold text-zinc-800">{{ $gameMatch->season->name }}</span>」の個人ポイント（<code class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs font-mono">season_player_points</code>）を、
            そのシーズン内の<strong>すべての確定済み試合</strong>の <code class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs font-mono">match_results</code> から作り直します（<strong>match_results 自体は変えません</strong>）。
        </p>
        <p class="mt-2 text-sm text-zinc-500">ポイントのルール修正後は、上の「試合の順位・ポイントを再計算」を先に実行してください。</p>
        <form method="post" action="{{ route('admin.matches.recalculate-season-player-points', $gameMatch) }}" class="mt-5" onsubmit="return confirm('このシーズンの個人順位を再集計しますか？');">
            @csrf
            <button type="submit" class="kfc-btn-sky">
                個人順位を再集計する
            </button>
        </form>
    </div>

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">この大会に紐づく釣果</h2>
        <p class="kfc-muted mt-2">承認済みの数値・画像の修正や、承認状態の変更ができます。保存後、この試合の順位を再計算します（試合が確定済みのときはシーズン個人ポイントも更新）。</p>

        @if ($matchCatches->isEmpty())
            <p class="kfc-muted mt-6">まだ釣果の投稿がありません。</p>
        @else
            <div class="kfc-table-shell mt-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="kfc-thead">
                        <tr>
                            <th class="px-4 py-3">投稿日時</th>
                            <th class="px-4 py-3">チーム</th>
                            <th class="px-4 py-3">プレイヤー</th>
                            <th class="px-4 py-3">cm / g</th>
                            <th class="px-4 py-3">状態</th>
                            <th class="px-4 py-3">画像</th>
                            <th class="px-4 py-3 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matchCatches as $c)
                            <tr class="kfc-trow">
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600">{{ $c->created_at->format('Y/m/d H:i') }}</td>
                                <td class="px-4 py-3 font-medium text-zinc-900">{{ $c->team?->name ?? '—（個人戦）' }}</td>
                                <td class="px-4 py-3">{{ $c->player->displayLabel() }}</td>
                                <td class="px-4 py-3 tabular-nums text-zinc-700">{{ $c->length_cm }} / {{ $c->weight_g }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1',
                                        'bg-amber-50 text-amber-900 ring-amber-200/70' => $c->approval_status === \App\Enums\CatchApprovalStatus::Pending,
                                        'bg-emerald-50 text-emerald-800 ring-emerald-200/70' => $c->approval_status === \App\Enums\CatchApprovalStatus::Approved,
                                        'bg-red-50 text-red-800 ring-red-200/70' => $c->approval_status === \App\Enums\CatchApprovalStatus::Rejected,
                                    ])>{{ $c->approval_status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-zinc-600">{{ $c->images->count() }}枚</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.matches.catches.edit', [$gameMatch, $c]) }}" class="kfc-link">編集</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
