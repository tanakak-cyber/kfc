@if (! isset($gameMatch))
    <div>
        <span class="kfc-label">試合形式</span>
        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            @php $mt = old('match_type', 'team'); @endphp
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="radio" name="match_type" value="team" class="text-emerald-600 focus:ring-emerald-500/40" @checked($mt === 'team') required>
                <span>チーム戦</span>
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="radio" name="match_type" value="individual" class="text-emerald-600 focus:ring-emerald-500/40" @checked($mt === 'individual')>
                <span>個人戦</span>
            </label>
        </div>
        <p class="mt-2 text-xs text-zinc-500">作成後は形式を変更できません。個人戦は参加者ごとに投稿URLを発行します。</p>
    </div>
@endif
<div>
    <label class="kfc-label">シーズン</label>
    <select name="season_id" class="kfc-select mt-2" required>
        @foreach ($seasons as $s)
            <option value="{{ $s->id }}" @selected(old('season_id', $gameMatch->season_id ?? $selectedSeasonId) == $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="kfc-label">試合名</label>
    <input type="text" name="title" value="{{ old('title', $gameMatch->title ?? '') }}" required class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">開始日時</label>
    <input type="datetime-local" name="start_datetime" value="{{ old('start_datetime', optional($gameMatch)->start_datetime?->format('Y-m-d\TH:i')) }}" required class="kfc-input mt-2">
    <p class="mt-1 text-xs text-zinc-500">この時刻より前は釣果投稿を受け付けません（Asia/Tokyo）。</p>
</div>
<div>
    <label class="kfc-label">終了日時（任意）</label>
    <input type="datetime-local" name="end_datetime" value="{{ old('end_datetime', optional($gameMatch)->end_datetime?->format('Y-m-d\TH:i')) }}" class="kfc-input mt-2">
    <p class="mt-1 text-xs text-zinc-500">未入力なら終了後も投稿可能。入力時はその時刻以降は投稿不可。</p>
</div>
<div>
    <label class="kfc-label">フィールド</label>
    <input type="text" name="field" value="{{ old('field', $gameMatch->field ?? '') }}" required class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">出艇店舗</label>
    <input type="text" name="launch_shop" value="{{ old('launch_shop', $gameMatch->launch_shop ?? '') }}" class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">ルール</label>
    <textarea name="rules" rows="4" class="kfc-input mt-2">{{ old('rules', $gameMatch->rules ?? '') }}</textarea>
</div>
