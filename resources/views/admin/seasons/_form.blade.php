<div>
    <label class="kfc-label">名前</label>
    <input type="text" name="name" value="{{ old('name', optional($season)->name ?? '') }}" required class="kfc-input mt-2">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="kfc-label">開始日</label>
        <input type="date" name="starts_on" value="{{ old('starts_on', optional($season)->starts_on?->format('Y-m-d')) }}" required class="kfc-input mt-2">
    </div>
    <div>
        <label class="kfc-label">終了日</label>
        <input type="date" name="ends_on" value="{{ old('ends_on', optional($season)->ends_on?->format('Y-m-d')) }}" required class="kfc-input mt-2">
    </div>
</div>
<div>
    <label class="kfc-label">説明</label>
    <textarea name="description" rows="4" class="kfc-input mt-2">{{ old('description', optional($season)->description ?? '') }}</textarea>
</div>
<div>
    <label class="kfc-label">画像</label>
    @if ($season && $season->image_path)
        @php $seasonImgUrl = asset('storage/'.$season->image_path); @endphp
        <p class="mt-2 text-xs text-zinc-500">現在: {{ $season->image_path }}</p>
        <img
            src="{{ $seasonImgUrl }}"
            alt=""
            class="mt-2 max-h-40 cursor-pointer rounded-xl border border-zinc-200 object-cover shadow-sm transition hover:opacity-95"
            role="button"
            tabindex="0"
            onclick="window.kfcOpenImageLightbox({{ json_encode($seasonImgUrl) }})"
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($seasonImgUrl) }});}"
        >
    @endif
    <input type="file" name="image" accept="image/*" class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_current" value="0">
    <input type="checkbox" name="is_current" value="1" id="is_current" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30" @checked(old('is_current', $season?->is_current ?? false))>
    <label for="is_current" class="text-sm text-zinc-700">現在シーズンにする</label>
</div>
