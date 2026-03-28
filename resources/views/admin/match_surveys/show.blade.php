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
                作成された試合:
                <a href="{{ route('admin.matches.edit', $survey->createdMatch) }}" class="kfc-link">{{ $survey->createdMatch->title }}</a>
            </p>
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

    @if ($survey->status !== \App\Enums\SurveyStatus::Finalized && $survey->dates->isNotEmpty() && $survey->fields->isNotEmpty())
        <div class="kfc-card mt-8 border-emerald-200/60 bg-emerald-50/20 ring-emerald-500/10">
            <h2 class="kfc-section-title">試合を確定する</h2>
            <p class="mt-3 text-sm leading-relaxed text-zinc-700">
                採用する<strong>日程</strong>と<strong>フィールド</strong>を選び、<strong>出席者</strong>にチェックを入れてから確定してください。
                日程を変えると、○ が付いている選手だけを自動でチェックします（いつでも手で変更可能）。
            </p>
            <p class="mt-2 text-sm text-amber-800">個人戦の場合のみ、チェックした選手分の <code class="rounded bg-white px-1 text-xs">match_participants</code> と投稿URLを自動発行します。チーム戦の場合は試合作成後にチーム登録が必要です。</p>

            <form method="post" action="{{ route('admin.match-surveys.finalize', $survey) }}" class="mt-6 space-y-5" id="finalize-survey-form" onsubmit="return confirm('試合を作成し、アンケートを確定しますか？\nこの操作は取り消せません。');">
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
                            チーム戦（後からチーム登録）
                        </label>
                    </div>
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
                <button type="submit" class="kfc-btn-emerald">確定して試合を作成</button>
            </form>
        </div>

        @push('scripts')
            <script>
                (function () {
                    const yesByDate = @json($playersWithYesForDate);
                    const sel = document.getElementById('match_survey_date_id');
                    function syncChecks() {
                        if (!sel) return;
                        const id = sel.value;
                        const yesSet = new Set((yesByDate[id] || []).map(Number));
                        document.querySelectorAll('#finalize-survey-form .player-pick').forEach(function (cb) {
                            cb.checked = yesSet.has(parseInt(cb.value, 10));
                        });
                    }
                    if (sel) {
                        sel.addEventListener('change', syncChecks);
                        document.addEventListener('DOMContentLoaded', syncChecks);
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
