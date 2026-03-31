@extends('layouts.app')

@section('title', $survey->title ?: '出欠アンケート')

@section('content')
    <div class="mx-auto max-w-lg">
        <h1 class="kfc-page-title text-xl sm:text-2xl">{{ $survey->title ?: '出欠アンケート' }}</h1>
        <p class="kfc-muted mt-2 text-sm">{{ $survey->season->name }}</p>

        @if (filled($survey->description))
            <div class="kfc-card-sm mt-6 whitespace-pre-line text-sm leading-relaxed text-zinc-700">{{ $survey->description }}</div>
        @endif

        @if ($survey->status === \App\Enums\SurveyStatus::Finalized)
            <p class="kfc-alert-success mt-8 text-sm" role="status">このアンケートは確定し、試合が作成されました。ありがとうございました。</p>
        @elseif ($survey->status === \App\Enums\SurveyStatus::Closed)
            <p class="kfc-alert-warn mt-8 text-sm" role="status">受付を終了しました。ただいま回答はできません。</p>
        @else
            <form method="post" action="{{ route('survey.store', $survey->survey_token) }}" class="kfc-card mt-8 space-y-6">
                @csrf
                <div>
                    <label class="kfc-label" for="player_id">お名前（選手）</label>
                    <select name="player_id" id="player_id" required class="kfc-select mt-2 min-h-12 text-base">
                        <option value="">選択してください</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-zinc-500">同じ選手で再度送信すると、前回の回答が上書きされます。</p>
                </div>

                <div>
                    <span class="kfc-label">候補日（各日について ○ か ×）</span>
                    <ul class="mt-3 space-y-3">
                        @foreach ($survey->dates as $d)
                            <li class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                                <p class="text-sm font-semibold text-zinc-900">{{ $d->on_date->format('Y年n月j日') }}</p>
                                <div class="mt-3 flex gap-4 text-base">
                                    @php $key = 'date_statuses.'.$d->id; @endphp
                                    <label class="inline-flex min-h-11 min-w-[5.5rem] cursor-pointer items-center justify-center gap-2 rounded-xl border-2 px-4 py-2 font-semibold has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-900 border-zinc-200 bg-white">
                                        <input type="radio" name="date_statuses[{{ $d->id }}]" value="yes" class="sr-only" @checked(old($key) === 'yes') required>
                                        ○ 行ける
                                    </label>
                                    <label class="inline-flex min-h-11 min-w-[5.5rem] cursor-pointer items-center justify-center gap-2 rounded-xl border-2 px-4 py-2 font-semibold has-[:checked]:border-zinc-600 has-[:checked]:bg-zinc-100 border-zinc-200 bg-white">
                                        <input type="radio" name="date_statuses[{{ $d->id }}]" value="no" class="sr-only" @checked(old($key) === 'no')>
                                        × 無理
                                    </label>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <label class="kfc-label" for="selected_field_id">希望のフィールド（1つ選択）</label>
                    <select name="selected_field_id" id="selected_field_id" required class="kfc-select mt-2 min-h-12 text-base">
                        <option value="">選択してください</option>
                        @foreach ($survey->fields as $f)
                            <option value="{{ $f->id }}" @selected(old('selected_field_id') == $f->id)>{{ $f->field_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="kfc-btn-primary min-h-12 w-full text-base font-semibold">回答を送信</button>
            </form>
        @endif
    </div>
@endsection
