@extends('layouts.app')

@section('title', '釣果投稿')

@section('content')
    <h1 class="kfc-page-title">釣果投稿</h1>
    @if ($entryMode === 'team')
        <p class="kfc-muted mt-2">{{ $gameMatch->title }} — <span class="font-medium text-zinc-700">{{ $team->name }}</span></p>
    @else
        <p class="kfc-muted mt-2">{{ $gameMatch->title }} — <span class="font-medium text-zinc-700">{{ $participant->player->displayLabel() }}</span>（個人戦）</p>
    @endif

    @error('match')
        <p class="kfc-alert-warn mt-6" role="alert">{{ $message }}</p>
    @enderror

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">TOP3（未承認含む）</h2>
        @if (empty($top3))
            <p class="kfc-muted mt-3">まだ投稿がありません。</p>
        @else
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-zinc-800">
                @foreach ($top3 as $row)
                    <li><span class="tabular-nums font-medium">{{ $row['weight_g'] }}</span> g / <span class="tabular-nums">{{ $row['length_cm'] }}</span> cm</li>
                @endforeach
            </ol>
        @endif
    </section>

    @if ($gameMatch->is_finalized)
        <p class="kfc-alert-warn mt-8" role="status">
            この試合は結果確定済みのため、新規投稿はできません。
        </p>
    @elseif ($gameMatch->isBeforeStartDatetime())
        <p class="kfc-alert-warn mt-8" role="status">
            試合開始前のため投稿できません。開始日時以降に再度アクセスしてください。
        </p>
    @elseif ($gameMatch->isAtOrAfterEndDatetime())
        <p class="kfc-alert-warn mt-8" role="status">
            試合終了後のため投稿できません。
        </p>
    @elseif ($entryMode === 'individual' && ! $participant->is_present)
        <p class="kfc-alert-warn mt-8" role="status">
            欠席登録のため、このURLからは投稿できません。
        </p>
    @else
        <section class="kfc-card mt-8 min-w-0 max-w-full">
            <h2 class="kfc-section-title">新規投稿</h2>
            <form
                id="entry-catch-form"
                method="post"
                action="{{ route('entry.store', $entryMode === 'team' ? $team->entry_token : $participant->entry_token) }}"
                enctype="multipart/form-data"
                class="mt-6 min-w-0 max-w-full space-y-5"
            >
                @csrf
                @if ($entryMode === 'team')
                    <div>
                        <label class="kfc-label">釣った人</label>
                        <select name="player_id" class="kfc-select mt-2" required>
                            @foreach ($team->players as $p)
                                <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="kfc-label">長さ（cm）</label>
                    <input type="number" step="0.01" name="length_cm" value="{{ old('length_cm') }}" class="kfc-input mt-2" required>
                </div>
                <div>
                    <label class="kfc-label">重さ（g）</label>
                    <input type="number" step="1" min="0" max="9999" name="weight_g" value="{{ old('weight_g') }}" class="kfc-input mt-2" required>
                </div>
                <div class="min-w-0 max-w-full">
                    <label class="kfc-label" for="entry-photos-input">写真（複数可）</label>
                    <p class="mt-2 text-xs font-medium text-amber-900/90">
                        ※標準カメラで撮影した画像のみ投稿可能です（編集・転送不可）
                    </p>
                    <input
                        id="entry-photos-input"
                        type="file"
                        name="photos[]"
                        accept="image/*"
                        multiple
                        class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100"
                    >
                    <p id="entry-photos-status" class="mt-2 text-sm font-medium text-zinc-700" aria-live="polite">まだ写真が選択されていません</p>
                    <p id="entry-photos-preview-label" class="mt-3 hidden text-xs font-medium text-zinc-600">選択中の写真（プレビュー）</p>
                    <ul
                        id="entry-photos-preview"
                        class="mt-2 hidden max-w-full list-none flex flex-wrap gap-3 overflow-x-auto p-0"
                        role="list"
                        aria-label="選択中の写真のサムネイル"
                    ></ul>
                    <button type="button" id="entry-photos-clear" class="mt-2 text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-2 hover:text-zinc-900">
                        写真の選択をすべてクリア
                    </button>
                    @error('photos')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-zinc-500">1〜10枚まで。スマホでは「もう一度ファイルを選ぶ」と枚数が<strong>足されていきます</strong>（上書きされません）。アップロード時にリサイズ・JPEG化されます。</p>
                </div>
                <button type="submit" id="entry-submit-btn" class="kfc-btn-primary w-full sm:w-auto">送信</button>
            </form>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('entry-catch-form');
            if (form) {
                var sent = false;
                form.addEventListener('submit', function (e) {
                    if (sent) {
                        e.preventDefault();
                        return;
                    }
                    sent = true;
                    var btn = document.getElementById('entry-submit-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.setAttribute('aria-busy', 'true');
                        btn.textContent = '送信中…';
                    }
                });
            }
        })();
    </script>
    <script>
        (function () {
            var input = document.getElementById('entry-photos-input');
            var statusEl = document.getElementById('entry-photos-status');
            var previewEl = document.getElementById('entry-photos-preview');
            var previewLabel = document.getElementById('entry-photos-preview-label');
            var clearBtn = document.getElementById('entry-photos-clear');
            if (!input || !input.multiple) return;

            var MAX = 10;
            var accumulator = new DataTransfer();
            var previewObjectUrls = [];

            function isDuplicate(list, file) {
                for (var i = 0; i < list.length; i++) {
                    var x = list[i];
                    if (x.name === file.name && x.size === file.size && x.lastModified === file.lastModified) return true;
                }
                return false;
            }

            function revokePreviewUrls() {
                previewObjectUrls.forEach(function (u) {
                    URL.revokeObjectURL(u);
                });
                previewObjectUrls = [];
            }

            function refreshPhotoPreviews() {
                revokePreviewUrls();
                if (!previewEl) return;
                previewEl.innerHTML = '';
                var n = accumulator.files.length;
                if (n === 0) {
                    previewEl.classList.add('hidden');
                    if (previewLabel) previewLabel.classList.add('hidden');
                    return;
                }
                previewEl.classList.remove('hidden');
                if (previewLabel) previewLabel.classList.remove('hidden');
                for (var i = 0; i < n; i++) {
                    var file = accumulator.files[i];
                    var url = URL.createObjectURL(file);
                    previewObjectUrls.push(url);
                    var li = document.createElement('li');
                    li.className = 'flex min-w-0 w-24 shrink-0 flex-col sm:w-28';
                    var frame = document.createElement('div');
                    frame.className =
                        'h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 shadow-sm sm:h-28 sm:w-28';
                    var img = document.createElement('img');
                    img.src = url;
                    img.alt = '';
                    img.className = 'block h-full w-full max-h-full max-w-full object-cover';
                    img.loading = 'lazy';
                    frame.appendChild(img);
                    var cap = document.createElement('p');
                    cap.className = 'mt-1 line-clamp-2 max-w-24 break-all text-center text-[0.65rem] leading-tight text-zinc-600 sm:max-w-28';
                    cap.textContent = file.name;
                    li.appendChild(frame);
                    li.appendChild(cap);
                    previewEl.appendChild(li);
                }
            }

            function applyToInput() {
                input.files = accumulator.files;
                if (statusEl) {
                    var n = accumulator.files.length;
                    statusEl.textContent = n === 0 ? 'まだ写真が選択されていません' : n + '枚選択中（最大' + MAX + '枚）';
                }
                refreshPhotoPreviews();
            }

            input.addEventListener('change', function () {
                var incoming = Array.prototype.slice.call(input.files || []);
                if (incoming.length === 0) return;

                var slots = MAX - accumulator.files.length;
                if (slots <= 0) {
                    alert('写真は最大' + MAX + '枚までです。');
                    applyToInput();
                    return;
                }

                var next = new DataTransfer();
                var i;
                for (i = 0; i < accumulator.files.length; i++) {
                    next.items.add(accumulator.files[i]);
                }
                var skippedForLimit = false;
                for (i = 0; i < incoming.length; i++) {
                    if (next.files.length >= MAX) {
                        skippedForLimit = i < incoming.length;
                        break;
                    }
                    var f = incoming[i];
                    if (!isDuplicate(Array.prototype.slice.call(next.files), f)) {
                        next.items.add(f);
                    }
                }
                if (skippedForLimit) {
                    alert('写真は最大' + MAX + '枚までです。');
                }
                accumulator = next;
                applyToInput();
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    accumulator = new DataTransfer();
                    applyToInput();
                });
            }
        })();
    </script>
@endpush
