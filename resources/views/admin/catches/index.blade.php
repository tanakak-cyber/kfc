@extends('layouts.admin')

@section('title', '釣果承認')

@section('content')
    <h1 class="kfc-page-title">未承認釣果</h1>
    <p class="kfc-muted mt-2">写真と数値を確認して承認または却下してください。</p>

    @if ($catches->count() > 0)
        <form
            method="post"
            action="{{ route('admin.catches.approve-batch') }}"
            class="mt-4"
            onsubmit="return confirm('このページに表示されている未承認釣果 {{ $catches->count() }} 件をすべて承認します。よろしいですか？');"
        >
            @csrf
            @foreach ($catches as $catch)
                <input type="hidden" name="catch_ids[]" value="{{ $catch->id }}">
            @endforeach
            <button
                type="submit"
                class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-700"
            >
                このページを一括承認（{{ $catches->count() }}件）
            </button>
        </form>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-2">
        <button
            type="button"
            id="kfc-batch-mode-start"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:bg-zinc-50"
        >
            向き一括変更を開始
        </button>
        <button
            type="button"
            id="kfc-batch-mode-end"
            class="hidden rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-800 shadow-sm transition hover:bg-zinc-50"
        >
            一括変更を終了
        </button>
    </div>

    <form id="kfc-batch-rotate-form" method="post" action="{{ route('admin.catches.images.rotate-batch') }}" class="sr-only" aria-hidden="true">
        @csrf
        <div id="kfc-batch-rotate-fields"></div>
    </form>

    <div
        id="kfc-batch-rotate-bar"
        class="fixed bottom-0 left-0 right-0 z-[200] hidden border-t border-white/15 bg-black px-4 py-3 text-white shadow-[0_-12px_40px_rgba(0,0,0,0.75)] md:left-60"
    >
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-white">
                <span class="font-semibold">向き一括変更中</span>
                <span id="kfc-batch-rotate-summary" class="ml-2 font-medium text-white"></span>
            </p>
            <button
                type="button"
                id="kfc-batch-rotate-save"
                disabled
                class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-950/30 transition hover:bg-emerald-700 focus-visible:outline focus-visible:ring-2 focus-visible:ring-emerald-400/80 disabled:cursor-not-allowed disabled:bg-emerald-800 disabled:text-white disabled:opacity-90 disabled:hover:bg-emerald-800"
            >
                画像の向きを保存
            </button>
        </div>
    </div>

    <div id="kfc-pending-catches" class="mt-8 space-y-6 pb-24">
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
                            <div class="flex w-36 flex-col gap-2 sm:w-40" data-kfc-image-block data-kfc-image-id="{{ $im->id }}">
                                <div
                                    class="kfc-rotate-preview overflow-hidden rounded-xl shadow-sm ring-1 ring-zinc-200/80 transition-transform duration-150"
                                    data-kfc-rotate-preview
                                    style="transform: rotate(0deg)"
                                >
                                    <img
                                        src="{{ $pendingImgUrl }}"
                                        alt=""
                                        class="h-36 w-36 cursor-pointer object-cover transition hover:opacity-95 sm:h-40 sm:w-40"
                                        role="button"
                                        tabindex="0"
                                        onclick='window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }})'
                                        onkeydown='if(event.key==="Enter"||event.key===" "){event.preventDefault();window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }});}'
                                    >
                                </div>
                                <div
                                    class="flex flex-wrap gap-1 opacity-40 pointer-events-none transition-opacity duration-150"
                                    data-kfc-rotate-controls
                                    aria-disabled="true"
                                >
                                    <button
                                        type="button"
                                        data-kfc-rot-ccw
                                        class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50"
                                        title="反時計回りに90°（一括変更モード）"
                                    >
                                        ↺ 左90°
                                    </button>
                                    <button
                                        type="button"
                                        data-kfc-rot-cw
                                        class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50"
                                        title="時計回りに90°（一括変更モード）"
                                    >
                                        ↻ 右90°
                                    </button>
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var root = document.getElementById('kfc-pending-catches');
                if (!root) return;

                var toggleBtn = document.getElementById('kfc-batch-mode-start');
                var endBtn = document.getElementById('kfc-batch-mode-end');
                var bar = document.getElementById('kfc-batch-rotate-bar');
                var summary = document.getElementById('kfc-batch-rotate-summary');
                var saveBtn = document.getElementById('kfc-batch-rotate-save');
                var form = document.getElementById('kfc-batch-rotate-form');
                var fields = document.getElementById('kfc-batch-rotate-fields');

                var state = Object.create(null);
                var active = false;

                function effectiveQuarters(net) {
                    net = net || 0;
                    return ((net % 4) + 4) % 4;
                }

                function countPendingImages() {
                    var n = 0;
                    Object.keys(state).forEach(function (id) {
                        if (effectiveQuarters(state[id]) !== 0) n++;
                    });
                    return n;
                }

                function updatePreviews() {
                    root.querySelectorAll('[data-kfc-image-block]').forEach(function (block) {
                        var id = block.getAttribute('data-kfc-image-id');
                        var preview = block.querySelector('[data-kfc-rotate-preview]');
                        if (!preview) return;
                        var q = effectiveQuarters(state[id]);
                        preview.style.transform = 'rotate(' + String(q * 90) + 'deg)';
                    });

                    var cnt = countPendingImages();
                    if (summary) {
                        summary.textContent =
                            cnt === 0
                                ? '（未保存の回転はありません）'
                                : '未保存の回転: ' + String(cnt) + '枚';
                    }
                    if (saveBtn) saveBtn.disabled = cnt === 0;

                    if (bar) {
                        if (active && cnt > 0) bar.classList.remove('hidden');
                        else bar.classList.add('hidden');
                    }
                }

                function setControlsEnabled(on) {
                    root.querySelectorAll('[data-kfc-rotate-controls]').forEach(function (el) {
                        el.classList.toggle('opacity-40', !on);
                        el.classList.toggle('pointer-events-none', !on);
                        el.setAttribute('aria-disabled', on ? 'false' : 'true');
                    });
                }

                function setActive(on) {
                    active = on;
                    setControlsEnabled(on);
                    if (toggleBtn) toggleBtn.classList.toggle('hidden', on);
                    if (endBtn) endBtn.classList.toggle('hidden', !on);
                    if (!on) {
                        Object.keys(state).forEach(function (k) {
                            delete state[k];
                        });
                    }
                    updatePreviews();
                }

                root.addEventListener('click', function (e) {
                    var t = e.target;
                    if (!active) return;
                    var ccw = t.closest && t.closest('[data-kfc-rot-ccw]');
                    var cw = t.closest && t.closest('[data-kfc-rot-cw]');
                    if (!ccw && !cw) return;
                    var block = t.closest('[data-kfc-image-block]');
                    if (!block) return;
                    var id = block.getAttribute('data-kfc-image-id');
                    if (!id) return;
                    state[id] = (state[id] || 0) + (cw ? 1 : -1);
                    updatePreviews();
                });

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function () {
                        setActive(true);
                    });
                }
                if (endBtn) {
                    endBtn.addEventListener('click', function () {
                        var cnt = countPendingImages();
                        if (cnt > 0 && !confirm('未保存の回転を破棄して終了しますか？')) return;
                        setActive(false);
                    });
                }
                if (saveBtn && form && fields) {
                    saveBtn.addEventListener('click', function () {
                        fields.innerHTML = '';
                        Object.keys(state).forEach(function (id) {
                            var net = state[id] || 0;
                            if (net === 0) return;
                            if (effectiveQuarters(net) === 0) return;
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'rotations[' + id + ']';
                            input.value = String(net);
                            fields.appendChild(input);
                        });
                        if (!fields.children.length) return;
                        form.submit();
                    });
                }
            });
        </script>
    @endpush
@endsection
