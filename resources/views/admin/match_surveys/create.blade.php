@extends('layouts.admin')

@section('title', 'アンケート作成')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.match-surveys.index') }}" class="kfc-link">一覧に戻る</a></p>
    <h1 class="kfc-page-title mt-2">出欠アンケートを作成</h1>
    <p class="kfc-muted mt-2">候補日・フィールドは1件以上必要です。空欄の行は無視されます。</p>

    <form method="post" action="{{ route('admin.match-surveys.store') }}" class="kfc-card mt-8 max-w-2xl space-y-6">
        @csrf
        <div>
            <label class="kfc-label" for="season_id">シーズン</label>
            <select name="season_id" id="season_id" class="kfc-select mt-2" required>
                @foreach ($seasons as $se)
                    <option value="{{ $se->id }}" @selected(old('season_id') == $se->id)>{{ $se->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="kfc-label" for="title">タイトル（任意）</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" class="kfc-input mt-2" placeholder="例: 6月例会 日程調整">
        </div>
        <div>
            <label class="kfc-label" for="description">説明（任意）</label>
            <textarea name="description" id="description" rows="3" class="kfc-input mt-2" placeholder="LINEの代わりにこちらで回答お願いします など">{{ old('description') }}</textarea>
        </div>
        <div>
            <span class="kfc-label">候補日（複数）</span>
            <p class="mt-1 text-xs text-zinc-500">空欄の行は送られません。最低1日は入力してください。</p>
            <div class="mt-3 space-y-2">
                @foreach (range(0, 5) as $i)
                    <input type="date" name="dates[]" value="{{ old('dates.'.$i) }}" class="kfc-input">
                @endforeach
            </div>
        </div>
        <div>
            <span class="kfc-label">候補フィールド（複数）</span>
            <p class="mt-1 text-xs text-zinc-500">最低1つは入力してください。空欄は無視されます。</p>
            <div class="mt-3 space-y-2">
                @foreach (range(0, 5) as $i)
                    <input type="text" name="field_names[]" value="{{ old('field_names.'.$i) }}" class="kfc-input" placeholder="例: 琵琶湖 北湖">
                @endforeach
            </div>
        </div>
        <button type="submit" class="kfc-btn-primary">作成</button>
    </form>
@endsection
