@extends('layouts.admin')

@section('title', '試合チーム')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.matches.index', ['season_id' => $gameMatch->season_id]) }}" class="kfc-link">試合一覧</a></p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }} — チーム</h1>
    <p class="mt-1 text-sm text-zinc-600">{{ $gameMatch->season->name }}</p>

    @include('admin.partials.survey_match_rsvp_banner', ['gameMatch' => $gameMatch])

    @include('admin.partials.mail_transport_notice')

    @php
        $canBulkEntryMail = $gameMatch->teams->contains(
            fn ($t) => $t->players->contains(fn ($p) => filled($p->email))
        );
    @endphp
    @if ($canBulkEntryMail)
        <form method="post" action="{{ route('admin.matches.entry-mail.all', $gameMatch) }}" class="mt-4" onsubmit="return confirm('出席者・チームメンバーへ、登録メールがある人すべてに釣果投稿URLを送信します。よろしいですか？');">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-lg border border-sky-300 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-900 shadow-sm transition hover:bg-sky-100">
                参加者全員にメールを送信
            </button>
            <p class="mt-2 text-xs text-zinc-500">チームメンバーにメールが登録されている選手へ、それぞれの投稿URL（チーム単位は同一URL）を送ります。</p>
        </form>
    @endif

    @if (! $gameMatch->is_finalized)
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">自動チーム編成</h2>
            <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                この試合のシーズンにおける<strong>確定済み試合から集計した個人ポイント合計</strong>で強い順に並べ、<strong>1位と最下位、2位と下から2番目…</strong>のようにペアを組みます（奇数のとき最後の1人は1人チーム）。アンケートから試合作成時の自動編成と同じロジックです。
            </p>
            @if ($autoTeamFormUsesSurveyAllowlist)
                <p class="mt-2 text-sm text-emerald-900/90">
                    <strong>アンケートから作成した試合のため</strong>、この表には<strong>確定した候補日に「出席（〇）」と答えた選手のみ</strong>が表示されます。×の日・未回答の選手は自動編成の候補に含めません（下の「チームを追加」では全選手から選べます）。
                </p>
            @endif
            @if ($gameMatch->teams->isNotEmpty())
                <p class="mt-2 text-sm text-amber-900/90">
                    すでにチームがある場合は「既存チームを削除してから再編成」にチェックを入れないと送信できません。
                    @if ($hasMatchCatches)
                        <span class="font-semibold">この試合に釣果があるため、一括の作り直しはできません。</span>親子などの都合で並びを直す場合は、下の「チーム名・メンバーを変更」から手動で入れ替えてください。
                    @endif
                </p>
            @endif

            <form
                method="post"
                action="{{ route('admin.matches.teams.auto-form', $gameMatch) }}"
                class="mt-6 space-y-5"
                id="auto-team-form"
                onsubmit="const cb = this.querySelector('input[name=replace_existing]'); if (cb && cb.checked) { return confirm('既存のチームをすべて削除し、選んだ選手で自動編成し直します。よろしいですか？'); } return true;"
            >
                @csrf
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="auto-select-all" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">全員選択</button>
                    <button type="button" id="auto-select-unassigned" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">未所属のみ選択</button>
                    <button type="button" id="auto-clear" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">クリア</button>
                </div>

                <div class="kfc-table-shell max-h-[min(28rem,55vh)] overflow-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="kfc-thead sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2">参加</th>
                                <th class="px-3 py-2">選手</th>
                                <th class="px-3 py-2 tabular-nums">シーズンpt</th>
                                <th class="px-3 py-2">この試合の所属</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($autoTeamPlayers as $p)
                                @php
                                    $pts = (int) ($seasonTotals[$p->id] ?? 0);
                                    $assigned = isset($playerIdToTeamName[$p->id]);
                                @endphp
                                <tr class="kfc-trow" data-player-id="{{ $p->id }}" data-assigned="{{ $assigned ? '1' : '0' }}">
                                    <td class="px-3 py-2 align-middle">
                                        <input
                                            type="checkbox"
                                            name="player_ids[]"
                                            value="{{ $p->id }}"
                                            class="auto-player-cb h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/40"
                                        >
                                    </td>
                                    <td class="px-3 py-2">{{ $p->displayLabel() }}</td>
                                    <td class="px-3 py-2 tabular-nums text-zinc-700">{{ $pts }}</td>
                                    <td class="px-3 py-2 text-zinc-600">{{ $assigned ? $playerIdToTeamName[$p->id] : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-zinc-500">
                                        候補日に出席（〇）の選手がいません。アンケート回答を確認するか、下の「チームを追加」から登録してください。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($gameMatch->teams->isNotEmpty())
                    <label class="inline-flex cursor-pointer items-start gap-2 text-sm {{ $hasMatchCatches ? 'cursor-not-allowed opacity-50' : '' }}">
                        <input
                            type="checkbox"
                            name="replace_existing"
                            value="1"
                            class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/40"
                            @disabled($hasMatchCatches)
                        >
                        <span>
                            既存のチームをすべて削除してから再編成する
                            @if ($hasMatchCatches)
                                <span class="block text-xs text-red-800">釣果があるため利用不可</span>
                            @endif
                        </span>
                    </label>
                @endif

                @error('player_ids')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    class="kfc-btn-primary"
                    @if(($hasMatchCatches && $gameMatch->teams->isNotEmpty()) || $autoTeamPlayers->isEmpty()) disabled @endif
                >
                    選択した選手で自動編成する
                </button>
                @if ($hasMatchCatches && $gameMatch->teams->isNotEmpty())
                    <p class="text-xs text-zinc-500">釣果があるため「既存削除付き」の自動編成は無効です。未登録の試合ではチェックを入れて作り直せます。</p>
                @endif
            </form>
        </section>

        @push('scripts')
            <script>
                (function () {
                    const form = document.getElementById('auto-team-form');
                    if (!form) return;
                    const boxes = function () { return form.querySelectorAll('.auto-player-cb'); };
                    document.getElementById('auto-select-all')?.addEventListener('click', function () {
                        boxes().forEach(function (cb) { cb.checked = true; });
                    });
                    document.getElementById('auto-clear')?.addEventListener('click', function () {
                        boxes().forEach(function (cb) { cb.checked = false; });
                    });
                    document.getElementById('auto-select-unassigned')?.addEventListener('click', function () {
                        form.querySelectorAll('tr[data-assigned="0"] .auto-player-cb').forEach(function (cb) { cb.checked = true; });
                        form.querySelectorAll('tr[data-assigned="1"] .auto-player-cb').forEach(function (cb) { cb.checked = false; });
                    });
                })();
            </script>
        @endpush
    @endif

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">登録チーム・投稿URL</h2>
        <ul class="mt-6 space-y-4 text-sm">
            @forelse ($gameMatch->teams as $team)
                @php
                    $members = $team->players->values();
                    $memberA = $members[0] ?? null;
                    $memberB = $members[1] ?? null;
                    $tid = $team->id;
                @endphp
                <li class="kfc-card-nested !p-5">
                    <p class="font-semibold text-zinc-900">{{ $team->name }}</p>
                    <p class="mt-1 text-zinc-600">{{ $team->players->map(fn ($p) => $p->displayLabel())->implode(' / ') }}</p>
                    <p class="mt-3 break-all text-xs text-zinc-500">
                        投稿URL:
                        <a href="{{ route('entry.show', $team->entry_token) }}" class="kfc-link" target="_blank" rel="noopener noreferrer">{{ url('/entry/'.$team->entry_token) }}</a>
                    </p>
                    @include('admin.partials.entry_share_copy_button', [
                        'gameMatch' => $gameMatch,
                        'entryUrl' => url('/entry/'.$team->entry_token),
                        'entryMailAction' => $team->players->contains(fn ($p) => filled($p->email))
                            ? route('admin.matches.entry-mail.team', [$gameMatch, $team])
                            : null,
                    ])
                    @if (! $gameMatch->is_finalized)
                        <div class="mt-5 border-t border-zinc-100 pt-5">
                            <p class="text-sm font-medium text-zinc-800">チーム名・メンバーを変更（手動）</p>
                            <p class="mt-1 text-xs text-zinc-500">親子参加などで同じチームにしたい場合は、ここでメンバーを入れ替えてください。</p>
                            <form method="post" action="{{ route('admin.matches.teams.update', [$gameMatch, $team]) }}" class="mt-4 max-w-lg space-y-4">
                                @csrf
                                @method('put')
                                <div>
                                    <label class="kfc-label" for="team_name_{{ $tid }}">チーム名</label>
                                    <input
                                        type="text"
                                        id="team_name_{{ $tid }}"
                                        name="team_name_{{ $tid }}"
                                        value="{{ old('team_name_'.$tid, $team->name) }}"
                                        required
                                        class="kfc-input mt-2"
                                    >
                                    @error('team_name_'.$tid)
                                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="kfc-label" for="member_a_id_{{ $tid }}">メンバー1（必須）</label>
                                    <select name="member_a_id_{{ $tid }}" id="member_a_id_{{ $tid }}" class="kfc-select mt-2" required>
                                        <option value="">選択</option>
                                        @foreach ($players as $p)
                                            <option value="{{ $p->id }}" @selected(old('member_a_id_'.$tid, $memberA?->id) == $p->id)>{{ $p->displayLabel() }}</option>
                                        @endforeach
                                    </select>
                                    @error('member_a_id_'.$tid)
                                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="kfc-label" for="member_b_id_{{ $tid }}">メンバー2（任意）</label>
                                    <select name="member_b_id_{{ $tid }}" id="member_b_id_{{ $tid }}" class="kfc-select mt-2">
                                        <option value="">1人チームの場合は空のまま</option>
                                        @foreach ($players as $p)
                                            <option value="{{ $p->id }}" @selected(old('member_b_id_'.$tid, $memberB?->id) == $p->id)>{{ $p->displayLabel() }}</option>
                                        @endforeach
                                    </select>
                                    @error('member_b_id_'.$tid)
                                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="kfc-btn-primary">このチームを保存</button>
                            </form>
                        </div>
                        <form method="post" action="{{ route('admin.matches.teams.destroy', [$gameMatch, $team]) }}" class="mt-4" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:underline">チーム削除</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="kfc-muted">チームがまだありません。</li>
            @endforelse
        </ul>
    </section>

    @if (! $gameMatch->is_finalized)
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">チームを追加</h2>
            <form method="post" action="{{ route('admin.matches.teams.store', $gameMatch) }}" class="mt-6 max-w-lg space-y-5">
                @csrf
                <div>
                    <label class="kfc-label">チーム名</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="kfc-input mt-2">
                </div>
                <div>
                    <label class="kfc-label">メンバー1（必須）</label>
                    <select name="player_a_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_a_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kfc-label">メンバー2（任意）</label>
                    <select name="player_b_id" class="kfc-select mt-2">
                        <option value="">1人チームの場合は空のまま</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_b_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">1人チームも登録できます。2人目を選ぶと通常の2人チームになります。</p>
                </div>
                @error('player_a_id')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="kfc-btn-primary">追加</button>
            </form>
        </section>
    @endif
@endsection
