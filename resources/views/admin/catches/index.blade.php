@extends('layouts.admin')

@section('title', '釣果承認')

@section('content')
    <h1 class="kfc-page-title">未承認釣果</h1>
    <p class="kfc-muted mt-2">写真と数値を確認して承認または却下してください。</p>
    <div class="mt-8 space-y-6">
        @forelse ($catches as $catch)
            <div class="kfc-card-sm sm:flex sm:gap-6">
                @php
                    $pendingUrls = $catch->images
                        ->map(fn ($im) => asset('storage/'.$im->path).'?v='.($im->updated_at?->timestamp ?? $im->id))
                        ->values()
                        ->all();
                @endphp
                @if (count($pendingUrls) > 0)
                    <div class="flex shrink-0 flex-wrap gap-3 sm:max-w-md">
                        @foreach ($catch->images as $idx => $im)
                            @php
                                $pendingImgUrl = asset('storage/'.$im->path).'?v='.($im->updated_at?->timestamp ?? $im->id);
                            @endphp
                            <div class="flex w-36 flex-col gap-2 sm:w-40">
                                <img
                                    src="{{ $pendingImgUrl }}"
                                    alt=""
                                    class="h-36 w-36 cursor-pointer rounded-xl object-cover shadow-sm ring-1 ring-zinc-200/80 transition hover:opacity-95 sm:h-40 sm:w-40"
                                    role="button"
                                    tabindex="0"
                                    onclick='window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }})'
                                    onkeydown='if(event.key==="Enter"||event.key===" "){event.preventDefault();window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }});}'
                                >
                                <div class="flex flex-wrap gap-1">
                                    <form method="post" action="{{ route('admin.catches.images.rotate', [$catch, $im]) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="direction" value="ccw">
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50"
                                            title="反時計回りに90°"
                                        >
                                            ↺ 左90°
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.catches.images.rotate', [$catch, $im]) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="direction" value="cw">
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50"
                                            title="時計回りに90°"
                                        >
                                            ↻ 右90°
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="mt-4 flex-1 text-sm sm:mt-0">
                    @php $errBag = 'pending_catch_'.$catch->id; @endphp
                    <p class="font-semibold text-zinc-900">{{ $catch->player->displayLabel() }}</p>
                    <form method="post" action="{{ route('admin.catches.measurements.update', $catch) }}" class="mt-3 max-w-md space-y-3 rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-4">
                        @csrf
                        <p class="text-xs font-medium text-zinc-600">申請サイズ（写真と照らして修正できます）</p>
                        <div class="flex flex-wrap gap-4">
                            <div class="min-w-[8rem] flex-1">
                                <label class="kfc-label" for="len-{{ $catch->id }}">長さ（cm）</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="length_cm"
                                    id="len-{{ $catch->id }}"
                                    value="{{ old('length_cm', $catch->length_cm) }}"
                                    required
                                    class="kfc-input mt-1.5"
                                >
                                @error('length_cm', $errBag)
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="min-w-[8rem] flex-1">
                                <label class="kfc-label" for="wgt-{{ $catch->id }}">重さ（g）</label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    max="9999"
                                    name="weight_g"
                                    id="wgt-{{ $catch->id }}"
                                    value="{{ old('weight_g', $catch->weight_g) }}"
                                    required
                                    class="kfc-input mt-1.5"
                                >
                                @error('weight_g', $errBag)
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-900 shadow-sm hover:bg-emerald-50">
                            数値を保存
                        </button>
                    </form>
                    <p class="mt-2 text-zinc-500">{{ $catch->gameMatch->title }} — {{ $catch->team?->name ?? '個人戦' }}</p>
                    <p class="text-xs text-zinc-400">{{ $catch->created_at->format('Y/m/d H:i') }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('admin.catches.approve', $catch) }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-700">承認</button>
                        </form>
                        <form method="post" action="{{ route('admin.catches.reject', $catch) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">却下</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="kfc-muted">未承認の釣果はありません。</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $catches->links() }}</div>
@endsection
