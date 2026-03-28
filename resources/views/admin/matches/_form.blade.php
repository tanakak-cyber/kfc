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
    <label class="kfc-label">日時</label>
    <input type="datetime-local" name="held_at" value="{{ old('held_at', optional($gameMatch)->held_at?->format('Y-m-d\TH:i')) }}" required class="kfc-input mt-2">
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
<div>
    <label class="kfc-label">ステータス</label>
    @php
        $st = old('status', optional($gameMatch)->status?->value ?? 'scheduled');
    @endphp
    <select name="status" class="kfc-select mt-2">
        <option value="scheduled" @selected($st === 'scheduled')>予定</option>
        <option value="in_progress" @selected($st === 'in_progress')>開催中</option>
        <option value="completed" @selected($st === 'completed')>完了</option>
    </select>
</div>
