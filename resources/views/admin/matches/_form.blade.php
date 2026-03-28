<div>
    <label class="block text-sm font-medium text-slate-700">シーズン</label>
    <select name="season_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
        @foreach ($seasons as $s)
            <option value="{{ $s->id }}" @selected(old('season_id', $gameMatch->season_id ?? $selectedSeasonId) == $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">試合名</label>
    <input type="text" name="title" value="{{ old('title', $gameMatch->title ?? '') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">日時</label>
    <input type="datetime-local" name="held_at" value="{{ old('held_at', optional($gameMatch)->held_at?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">フィールド</label>
    <input type="text" name="field" value="{{ old('field', $gameMatch->field ?? '') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">出艇店舗</label>
    <input type="text" name="launch_shop" value="{{ old('launch_shop', $gameMatch->launch_shop ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">ルール</label>
    <textarea name="rules" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('rules', $gameMatch->rules ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">ステータス</label>
    @php
        $st = old('status', optional($gameMatch)->status?->value ?? 'scheduled');
    @endphp
    <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="scheduled" @selected($st === 'scheduled')>予定</option>
        <option value="in_progress" @selected($st === 'in_progress')>開催中</option>
        <option value="completed" @selected($st === 'completed')>完了</option>
    </select>
</div>
