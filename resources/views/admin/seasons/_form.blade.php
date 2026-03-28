<div>
    <label class="block text-sm font-medium text-slate-700">名前</label>
    <input type="text" name="name" value="{{ old('name', $season->name ?? '') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">開始日</label>
        <input type="date" name="starts_on" value="{{ old('starts_on', optional($season)->starts_on?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">終了日</label>
        <input type="date" name="ends_on" value="{{ old('ends_on', optional($season)->ends_on?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">説明</label>
    <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('description', $season->description ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">画像</label>
    @if ($season && $season->image_path)
        @php $seasonImgUrl = asset('storage/'.$season->image_path); @endphp
        <p class="mt-1 text-xs text-slate-500">現在: {{ $season->image_path }}</p>
        <img
            src="{{ $seasonImgUrl }}"
            alt=""
            class="mt-2 max-h-40 cursor-pointer rounded-lg border border-slate-200 object-cover hover:opacity-95"
            role="button"
            tabindex="0"
            onclick="window.kfcOpenImageLightbox({{ json_encode($seasonImgUrl) }})"
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($seasonImgUrl) }});}"
        >
    @endif
    <input type="file" name="image" accept="image/*" class="mt-1 w-full text-sm">
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_current" value="0">
    <input type="checkbox" name="is_current" value="1" id="is_current" class="rounded border-slate-300" @checked(old('is_current', $season?->is_current ?? false))>
    <label for="is_current" class="text-sm text-slate-700">現在シーズンにする</label>
</div>
