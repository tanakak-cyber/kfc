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

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">選手への追加ポイント</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-600">
            特典やイレギュラーで、特定の選手にだけこの試合のシーズン集計へポイントを加算できます（ランキングの <code class="rounded bg-zinc-100 px-1 text-xs">match_results.points</code> には含まれません）。複数回・複数選手に登録できます。
        </p>
        @if ($bonusEligiblePlayers->isEmpty())
            <p class="kfc-muted mt-4">チームまたは参加者が未登録のため、まだ付与できません。</p>
        @else
            <form method="post" action="{{ route('admin.matches.player-bonus-points.store', $gameMatch) }}" class="mt-6 flex max-w-2xl flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                @csrf
                <div class="min-w-[12rem] flex-1">
                    <label class="kfc-label" for="bonus_player_id">選手</label>
                    <select name="player_id" id="bonus_player_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($bonusEligiblePlayers->sortBy(fn ($p) => $p->name) as $p)
                            <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-28">
                    <label class="kfc-label" for="bonus_points">ポイント</label>
                    <input type="number" name="points" id="bonus_points" min="1" max="99" value="{{ old('points', 2) }}" required class="kfc-input mt-2 tabular-nums">
                </div>
                <div class="min-w-0 flex-1 sm:basis-full">
                    <label class="kfc-label" for="bonus_reason">理由（任意）</label>
                    <input type="text" name="reason" id="bonus_reason" value="{{ old('reason') }}" maxlength="500" class="kfc-input mt-2" placeholder="例: 大会協力特典">
                </div>
                <button type="submit" class="kfc-btn-emerald shrink-0">追加する</button>
            </form>
            @error('player_id')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('points')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        @endif

        @if ($bonusPoints->isNotEmpty())
            <div class="kfc-table-shell mt-8 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="kfc-thead">
                        <tr>
                            <th class="px-4 py-3">選手</th>
                            <th class="px-4 py-3">追加pt</th>
                            <th class="px-4 py-3">理由</th>
                            <th class="px-4 py-3 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bonusPoints as $bp)
                            <tr class="kfc-trow">
                                <td class="px-4 py-3">{{ $bp->player->displayLabel() }}</td>
                                <td class="px-4 py-3 tabular-nums font-semibold text-emerald-800">+{{ $bp->points }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $bp->reason ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="post" action="{{ route('admin.matches.player-bonus-points.destroy', [$gameMatch, $bp]) }}" class="inline" onsubmit="return confirm('この追加ポイントを削除しますか？');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

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
            この試合の <code class="rounded-md bg-white px-1.5 py-0.5 text-xs font-mono ring-1 ring-zinc-200">match_results</code> を、いまの<strong>順位算出設定（重さ／長さ・本数）</strong>とルール（承認済み釣果0件は<strong>1ポイント</strong>など）で<strong>上書き</strong>し、そのあとシーズン個人順位も更新します。
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
