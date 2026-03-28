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
