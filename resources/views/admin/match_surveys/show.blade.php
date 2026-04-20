@extends('layouts.admin')

@section('title', $survey->title ?: 'アンケート詳細')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.match-surveys.index') }}" class="kfc-link">一覧に戻る</a></p>
    <h1 class="kfc-page-title mt-2">{{ $survey->title ?: '（無題のアンケート）' }}</h1>
    <p class="mt-1 text-sm text-zinc-600">{{ $survey->season->name }} · <span class="kfc-badge">{{ $survey->status->label() }}</span></p>

    @if (filled($survey->description))
        <p class="mt-4 whitespace-pre-line text-sm text-zinc-700">{{ $survey->description }}</p>
    @endif

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">回答用URL</h2>
        <p class="mt-3 break-all text-sm">
            <a href="{{ route('survey.show', $survey->survey_token) }}" class="kfc-link" target="_blank" rel="noopener noreferrer">{{ url('/survey/'.$survey->survey_token) }}</a>
        </p>
        @if ($survey->createdMatch)
            <p class="mt-4 text-sm text-zinc-600">
                @if ($survey->status === \App\Enums\SurveyStatus::Finalized)
                    直近に作成した試合（アンケートに紐づくリンク）:
                @else
                    作成された試合:
                @endif
                <a href="{{ route('admin.matches.edit', $survey->createdMatch) }}" class="kfc-link">{{ $survey->createdMatch->title }}</a>
            </p>
            @if ($survey->status === \App\Enums\SurveyStatus::Finalized)
                <p class="mt-2 text-xs text-zinc-500">複数回試合作成した場合も、ここは最後に作成した試合のみ表示します。以前の試合は試合一覧から開いてください。</p>
            @endif
        @endif
    </div>

    @if ($survey->status !== \App\Enums\SurveyStatus::Finalized)
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($survey->status === \App\Enums\SurveyStatus::Open)
                <form method="post" action="{{ route('admin.match-surveys.close', $survey) }}" onsubmit="return confirm('受付を終了しますか？');">
                    @csrf
                    <button type="submit" class="rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 shadow-sm hover:bg-zinc-50">受付を終了する</button>
                </form>
            @else
                <form method="post" action="{{ route('admin.match-surveys.reopen', $survey) }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm hover:bg-emerald-100">受付を再開する</button>
                </form>
            @endif
        </div>
    @endif

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">日程ごとの「○」の数</h2>
        <div class="kfc-table-shell mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="kfc-thead">
                    <tr>
                        <th class="px-4 py-3">候補日</th>
                        <th class="px-4 py-3 tabular-nums">○ の人数</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($survey->dates as $d)
                        <tr class="kfc-trow">
                            <td class="px-4 py-3">{{ $d->on_date->format('Y/m/d') }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">{{ $dateYesCounts[$d->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">フィールドの人気（選択数）</h2>
        <div class="kfc-table-shell mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="kfc-thead">
                    <tr>
                        <th class="px-4 py-3">フィールド</th>
                        <th class="px-4 py-3 tabular-nums">選択数</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($survey->fields as $f)
                        <tr class="kfc-trow">
                            <td class="px-4 py-3">{{ $f->field_name }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">{{ $fieldPickCounts[$f->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="kfc-card mt-8">
        <h2 class="kfc-section-title">回答一覧</h2>
        @if ($survey->answers->isEmpty())
            <p class="kfc-muted mt-4">まだ回答がありません。</p>
        @else
            <div class="kfc-table-shell mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="kfc-thead">
                        <tr>
                            <th class="px-4 py-3">選手</th>
                            <th class="px-4 py-3">フィールド</th>
                            @foreach ($survey->dates as $d)
                                <th class="px-4 py-3 whitespace-nowrap">{{ $d->on_date->format('n/j') }}</th>
                            @endforeach
                            <th class="px-4 py-3">更新</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($survey->answers as $ans)
                            @php
                                $byDate = $ans->dateAnswers->keyBy('date_id');
                            @endphp
                            <tr class="kfc-trow">
                                <td class="px-4 py-3 font-medium">{{ $ans->player->displayLabel() }}</td>
                                <td class="px-4 py-3">{{ $ans->selectedField->field_name }}</td>
                                @foreach ($survey->dates as $d)
                                    @php $da = $byDate->get($d->id); @endphp
                                    <td class="px-4 py-3 text-center text-lg">{{ $da?->status?->label() ?? '—' }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-zinc-500">{{ $ans->updated_at->format('m/d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($survey->dates->isNotEmpty() && $survey->fields->isNotEmpty())
        <div class="kfc-card mt-8 border-emerald-200/60 bg-emerald-50/20 ring-emerald-500/10">
            <h2 class="kfc-section-title">{{ $survey->status === \App\Enums\SurveyStatus::Finalized ? '別の試合を追加作成' : '試合を確定する' }}</h2>
            @if ($survey->status === \App\Enums\SurveyStatus::Finalized)
                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm leading-relaxed text-amber-950">
                    すでに確定済みですが、<strong>別の試合を新規に作成</strong>できます（形式の取り違えの修正など）。既存の試合は<strong>自動では削除されません</strong>。不要な試合は試合一覧から削除してください。
                </p>
            @endif
            <p class="mt-3 text-sm leading-relaxed text-zinc-700">
                採用する<strong>日程</strong>と<strong>フィールド</strong>を選び、<strong>出席者</strong>にチェックを入れてから確定してください。
                日程を変えると、○ が付いている選手だけを自動でチェックします（いつでも手で変更可能）。
            </p>
            <p class="mt-2 text-sm text-amber-800">個人戦: チェックした選手を <code class="rounded bg-white px-1 text-xs">match_participants</code>（出席・投稿URL付き）に登録します。チーム戦: 「自動チーム編成」で <code class="rounded bg-white px-1 text-xs">teams</code> / <code class="rounded bg-white px-1 text-xs">team_members</code> を生成するか、オフにして従来どおり後から手動登録できます。</p>

            <form method="post" action="{{ route('admin.match-surveys.finalize', $survey) }}" class="mt-6 space-y-5" id="finalize-survey-form" onsubmit="return confirm({{ $survey->status === \App\Enums\SurveyStatus::Finalized ? "'新しい試合を追加作成しますか？\\n既存の試合は削除されません。'" : "'試合を作成し、アンケートを確定しますか？\\nこの操作は取り消せません。'" }});">
                @csrf
                <div>
                    <label class="kfc-label" for="match_survey_date_id">確定する日程</label>
                    <select name="match_survey_date_id" id="match_survey_date_id" class="kfc-select mt-2" required>
                        @foreach ($survey->dates as $d)
                            <option value="{{ $d->id }}">{{ $d->on_date->format('Y/m/d') }}（○ {{ $dateYesCounts[$d->id] ?? 0 }}名）</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kfc-label" for="match_survey_field_id">確定するフィールド</label>
                    <select name="match_survey_field_id" id="match_survey_field_id" class="kfc-select mt-2" required>
                        @foreach ($survey->fields as $f)
                            <option value="{{ $f->id }}">{{ $f->field_name }}（{{ $fieldPickCounts[$f->id] ?? 0 }}票）</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <span class="kfc-label">試合形式</span>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="match_type" value="individual" class="text-emerald-600" checked>
                            個人戦（参加者・URL自動発行）
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="match_type" value="team" class="text-emerald-600">
                            チーム戦
                        </label>
                    </div>
                </div>
                <div id="auto-team-block" class="hidden rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4">
                    <label class="flex cursor-pointer items-start gap-3 text-sm">
                        <input
                            type="checkbox"
                            name="auto_form_teams"
                            value="1"
                            class="mt-1 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30"
                            @checked(old('auto_form_teams'))
                        >
                        <span>
                            <span class="font-semibold text-zinc-900">自動チーム編成する</span>
                            <span class="mt-1 block text-xs leading-relaxed text-zinc-600">
                                出席者をシーズン累計ポイント降順（未登録は0点・同点は名前昇順）に並べ、<strong>先頭＋末尾</strong>のペアでチームを作成します。奇数の最後の1人は1人チームです。各チームに投稿用 <code class="rounded bg-white px-0.5 text-[0.65rem]">entry_token</code> を発行します。作成後は試合の「チーム」画面で名前・メンバーを編集できます。
                            </span>
                        </span>
                    </label>
                </div>
                <div>
                    <label class="kfc-label" for="match_title">試合タイトル</label>
                    <input type="text" name="match_title" id="match_title" value="{{ old('match_title', $survey->title ?: $survey->season->name.' 試合') }}" required class="kfc-input mt-2">
                </div>
                <div>
                    <label class="kfc-label" for="held_time">開始時刻</label>
                    <input type="time" name="held_time" id="held_time" value="{{ old('held_time', '09:00') }}" required class="kfc-input mt-2 max-w-[12rem]">
                    <p class="mt-1 text-xs text-zinc-500">日付は上で選んだ候補日が使われます。</p>
                </div>
                <div>
                    <span class="kfc-label">出席者（試合に含める選手）</span>
                    <p class="mt-1 text-xs text-zinc-500">全選手から選択できます。日程を変えると、その日○の人が自動チェックされます。</p>
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($allPlayers as $p)
                            <li class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2">
                                <input type="checkbox" name="player_ids[]" value="{{ $p->id }}" id="pl-{{ $p->id }}" class="player-pick rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30">
                                <label for="pl-{{ $p->id }}" class="flex-1 cursor-pointer text-sm">{{ $p->displayLabel() }}</label>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <button type="submit" class="kfc-btn-emerald">{{ $survey->status === \App\Enums\SurveyStatus::Finalized ? '追加で試合を作成' : '確定して試合を作成' }}</button>
            </form>
        </div>

        @push('scripts')
            <script>
                (function () {
                    const form = document.getElementById('finalize-survey-form');
                    const yesByDate = @json($playersWithYesForDate);
                    const sel = document.getElementById('match_survey_date_id');
                    const autoBlock = document.getElementById('auto-team-block');

                    function syncChecks() {
                        if (!sel) return;
                        const id = sel.value;
                        const yesSet = new Set((yesByDate[id] || []).map(Number));
                        document.querySelectorAll('#finalize-survey-form .player-pick').forEach(function (cb) {
                            cb.checked = yesSet.has(parseInt(cb.value, 10));
                        });
                    }

                    function toggleAutoTeamBlock() {
                        if (!form || !autoBlock) return;
                        const team = form.querySelector('input[name="match_type"][value="team"]')?.checked;
                        autoBlock.classList.toggle('hidden', !team);
                    }

                    if (sel) {
                        sel.addEventListener('change', syncChecks);
                    }
                    if (form) {
                        form.querySelectorAll('input[name="match_type"]').forEach(function (r) {
                            r.addEventListener('change', toggleAutoTeamBlock);
                        });
                    }
                    document.addEventListener('DOMContentLoaded', function () {
                        syncChecks();
                        toggleAutoTeamBlock();
                    });
                })();
            </script>
        @endpush
    @endif

    <div class="kfc-card mt-10 border-red-200/60 bg-red-50/20 ring-red-500/5">
        <h2 class="kfc-section-title--danger">アンケートを削除</h2>
        <p class="mt-3 text-sm leading-relaxed text-zinc-700">
            候補日・回答・集計データがすべて削除されます。この操作は元に戻せません。
        </p>
        @if ($survey->createdMatch)
            <p class="mt-2 text-sm text-amber-900">
                このアンケートから作成した試合（直近に紐づいている「<strong>{{ $survey->createdMatch->title }}</strong>」ほか、追加作成した分も含む）は<strong>削除されません</strong>。試合を消す場合は<a href="{{ route('admin.matches.edit', $survey->createdMatch) }}" class="kfc-link">試合編集</a>または試合一覧から削除してください。
            </p>
        @endif
        <form
            method="post"
            action="{{ route('admin.match-surveys.destroy', $survey) }}"
            class="mt-5"
            onsubmit="return confirm('このアンケートを完全に削除しますか？\n\n回答データはすべて失われます。');"
        >
            @csrf
            @method('delete')
            <button type="submit" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 shadow-sm transition hover:bg-red-50">
                このアンケートを削除する
            </button>
        </form>
    </div>
@endsection
