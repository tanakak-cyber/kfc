@extends('layouts.admin')

@section('title', '釣果を編集')

@section('content')
    <p class="kfc-muted">
        <a href="{{ route('admin.matches.edit', $gameMatch) }}" class="kfc-link">{{ $gameMatch->title }}</a>
        <span class="text-zinc-400"> · 試合編集</span>
    </p>
    <h1 class="kfc-page-title mt-1">釣果を編集</h1>
    <p class="kfc-muted mt-2">{{ $fishCatch->team->name }} · {{ $fishCatch->created_at->format('Y/m/d H:i') }} 投稿</p>

    <form
        method="post"
        action="{{ route('admin.matches.catches.update', [$gameMatch, $fishCatch]) }}"
        enctype="multipart/form-data"
        class="kfc-card mt-8 max-w-2xl space-y-5"
    >
        @csrf
        @method('put')

        <div>
            <label class="kfc-label" for="player_id">釣った人</label>
            <select name="player_id" id="player_id" class="kfc-select mt-2" required>
                @foreach ($fishCatch->team->players as $p)
                    <option value="{{ $p->id }}" @selected(old('player_id', $fishCatch->player_id) == $p->id)>{{ $p->displayLabel() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="kfc-label" for="length_cm">長さ（cm）</label>
            <input type="number" step="0.01" name="length_cm" id="length_cm" value="{{ old('length_cm', $fishCatch->length_cm) }}" class="kfc-input mt-2" required>
        </div>

        <div>
            <label class="kfc-label" for="weight_kg">重さ（kg）</label>
            <input type="number" step="0.001" name="weight_kg" id="weight_kg" value="{{ old('weight_kg', $fishCatch->weight_kg) }}" class="kfc-input mt-2" required>
        </div>

        <div>
            <label class="kfc-label" for="approval_status">承認状態</label>
            <select name="approval_status" id="approval_status" class="kfc-select mt-2" required>
                @foreach (\App\Enums\CatchApprovalStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(old('approval_status', $fishCatch->approval_status->value) === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500">承認済みにする場合は画像が1枚以上必要です。</p>
        </div>

        @if ($fishCatch->images->isNotEmpty())
            <div>
                <span class="kfc-label">現在の画像（削除するものにチェック）</span>
                @php
                    $galleryUrls = $fishCatch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
                @endphp
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($fishCatch->images as $idx => $im)
                        @php $u = asset('storage/'.$im->path); @endphp
                        <label class="flex cursor-pointer flex-col gap-2 rounded-xl border border-zinc-200 bg-zinc-50/80 p-2 ring-1 ring-zinc-100 transition hover:border-zinc-300">
                            <span class="relative block overflow-hidden rounded-lg">
                                <img
                                    src="{{ $u }}"
                                    alt=""
                                    class="h-28 w-28 object-cover"
                                    role="button"
                                    tabindex="0"
                                    onclick='window.kfcOpenImageLightbox(@json($galleryUrls), {{ $idx }})'
                                    onkeydown='if(event.key==="Enter"||event.key===" "){event.preventDefault();window.kfcOpenImageLightbox(@json($galleryUrls), {{ $idx }});}'
                                >
                            </span>
                            <span class="flex items-center justify-center gap-2 text-xs text-zinc-600">
                                <input type="checkbox" name="remove_image_ids[]" value="{{ $im->id }}" class="rounded border-zinc-300 text-red-600 focus:ring-red-500/30">
                                削除
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="kfc-label">画像を追加（任意）</label>
            <input type="file" name="photos[]" accept="image/*" multiple class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
            <p class="mt-2 text-xs text-zinc-500">最大10枚まで。リサイズ・JPEG化されます。</p>
            @error('photos')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="kfc-btn-primary">保存</button>
            <a href="{{ route('admin.matches.edit', $gameMatch) }}" class="kfc-link-subtle inline-flex items-center py-2.5 text-sm">試合編集に戻る</a>
        </div>
    </form>
@endsection
