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
        <h2 class="kfc-section-title">TOP{{ $entryTopLimit }}（未承認含む）</h2>
        @if (empty($topCatches))
            <p class="kfc-muted mt-3">まだ投稿がありません。</p>
        @else
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-zinc-800">
                @foreach ($topCatches as $row)
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
        @php
            $kfcMaxEntries = 20;
            $kfcDefaultRows = $entryMode === 'team'
                ? [['player_id' => (string) $team->players->first()->id, 'length_cm' => '', 'weight_g' => '']]
                : [['length_cm' => '', 'weight_g' => '']];
            $kfcEntryRows = old('entries', $kfcDefaultRows);
            if (! is_array($kfcEntryRows) || $kfcEntryRows === []) {
                $kfcEntryRows = $kfcDefaultRows;
            }
            $kfcNextEntryKey = count($kfcEntryRows) > 0 ? max(array_keys($kfcEntryRows)) + 1 : 1;
        @endphp
        <section class="kfc-card mt-8 min-w-0 max-w-full">
            <h2 class="kfc-section-title">新規投稿</h2>
            <p class="kfc-muted mt-2 text-sm">複数匹いる場合は「釣果を追加」で行を足し、それぞれにサイズと写真を入力してください（1回の送信で最大{{ $kfcMaxEntries }}件）。</p>
            <form
                id="entry-catch-form"
                method="post"
                action="{{ route('entry.store', $entryMode === 'team' ? $team->entry_token : $participant->entry_token) }}"
                enctype="multipart/form-data"
                class="mt-6 min-w-0 max-w-full space-y-5"
                data-max-entries="{{ $kfcMaxEntries }}"
                data-next-entry-key="{{ $kfcNextEntryKey }}"
                data-entry-mode="{{ $entryMode }}"
            >
                @csrf
                <p class="text-xs font-medium text-amber-900/90">
                    ※標準カメラで撮影した画像のみ投稿可能です（編集・転送不可）
                </p>

                <div id="entry-rows-container" class="space-y-5">
                    @foreach ($kfcEntryRows as $idx => $row)
                        @php $row = is_array($row) ? $row : []; @endphp
                        <div
                            class="entry-catch-row rounded-xl border border-zinc-200/80 bg-white p-4 shadow-sm ring-1 ring-zinc-100"
                            data-entry-row
                            data-entry-key="{{ $idx }}"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-3">
                                <h3 class="text-sm font-semibold text-zinc-800">
                                    釣果 <span class="tabular-nums entry-catch-row-label">{{ $loop->iteration }}</span>
                                </h3>
                                <button
                                    type="button"
                                    class="entry-row-remove rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-600 transition hover:bg-zinc-100"
                                    @if ($loop->count <= 1) hidden @endif
                                >
                                    この行を削除
                                </button>
                            </div>
                            <div class="mt-4 space-y-4">
                                @if ($entryMode === 'team')
                                    <div>
                                        <label class="kfc-label">釣った人</label>
                                        <select
                                            name="entries[{{ $idx }}][player_id]"
                                            class="kfc-select mt-2"
                                            required
                                        >
                                            @foreach ($team->players as $p)
                                                <option
                                                    value="{{ $p->id }}"
                                                    @selected(old('entries.'.$idx.'.player_id', $row['player_id'] ?? '') == $p->id)
                                                >{{ $p->displayLabel() }}</option>
                                            @endforeach
                                        </select>
                                        @error('entries.'.$idx.'.player_id')
                                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                <div>
                                    <label class="kfc-label">長さ（cm）</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="entries[{{ $idx }}][length_cm]"
                                        value="{{ old('entries.'.$idx.'.length_cm', $row['length_cm'] ?? '') }}"
                                        class="kfc-input mt-2"
                                        required
                                    >
                                    @error('entries.'.$idx.'.length_cm')
                                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="kfc-label">重さ（g）</label>
                                    <input
                                        type="number"
                                        step="1"
                                        min="0"
                                        max="9999"
                                        name="entries[{{ $idx }}][weight_g]"
                                        value="{{ old('entries.'.$idx.'.weight_g', $row['weight_g'] ?? '') }}"
                                        class="kfc-input mt-2"
                                        required
                                    >
                                    @error('entries.'.$idx.'.weight_g')
                                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="min-w-0 max-w-full">
                                    <label class="kfc-label" for="entry-photos-input-{{ $idx }}">写真（複数可）</label>
                                    <input
                                        id="entry-photos-input-{{ $idx }}"
                                        type="file"
                                        name="entries[{{ $idx }}][photos][]"
                                        accept="image/*"
                                        multiple
                                        class="entry-photos-input mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100"
                                    >
                                    <p class="entry-photos-status mt-2 text-sm font-medium text-zinc-700" aria-live="polite">まだ写真が選択されていません</p>
                                    <p class="entry-photos-preview-label mt-3 hidden text-xs font-medium text-zinc-600">選択中の写真（プレビュー）</p>
                                    <ul
                                        class="entry-photos-preview mt-2 hidden max-w-full list-none flex flex-wrap gap-3 overflow-x-auto p-0"
                                        role="list"
                                        aria-label="選択中の写真のサムネイル"
                                    ></ul>
                                    <button
                                        type="button"
                                        class="entry-photos-clear mt-2 text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-2 hover:text-zinc-900"
                                    >
                                        写真の選択をすべてクリア
                                    </button>
                                    @error('entries.'.$idx.'.photos')
                                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-2 text-xs text-zinc-500">1匹あたり1〜10枚。スマホでは「もう一度ファイルを選ぶ」と枚数が<strong>足されていきます</strong>。アップロード時にリサイズ・JPEG化されます。</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        id="entry-add-row-btn"
                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-100"
                    >
                        ＋ 釣果を追加
                    </button>
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
            var form = document.getElementById('entry-catch-form');
            var container = document.getElementById('entry-rows-container');
            var addBtn = document.getElementById('entry-add-row-btn');
            if (!form || !container) return;

            var MAX_ROWS = parseInt(form.getAttribute('data-max-entries') || '20', 10);
            var MAX_PHOTOS = 10;
            var nextKey = parseInt(form.getAttribute('data-next-entry-key') || '1', 10);

            function rowCount() {
                return container.querySelectorAll('[data-entry-row]').length;
            }

            function refreshRowLabels() {
                var rows = container.querySelectorAll('[data-entry-row]');
                rows.forEach(function (row, i) {
                    var lab = row.querySelector('.entry-catch-row-label');
                    if (lab) lab.textContent = String(i + 1);
                    var rm = row.querySelector('.entry-row-remove');
                    if (rm) rm.hidden = rows.length <= 1;
                });
            }

            function updateAddButton() {
                if (!addBtn) return;
                addBtn.disabled = rowCount() >= MAX_ROWS;
                addBtn.classList.toggle('opacity-50', addBtn.disabled);
            }

            function bindPhotoAccumulator(row) {
                var input = row.querySelector('.entry-photos-input');
                var statusEl = row.querySelector('.entry-photos-status');
                var previewEl = row.querySelector('.entry-photos-preview');
                var previewLabel = row.querySelector('.entry-photos-preview-label');
                var clearBtn = row.querySelector('.entry-photos-clear');
                if (!input || !input.multiple) return;

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
                        cap.className =
                            'mt-1 line-clamp-2 max-w-24 break-all text-center text-[0.65rem] leading-tight text-zinc-600 sm:max-w-28';
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
                        statusEl.textContent =
                            n === 0 ? 'まだ写真が選択されていません' : n + '枚選択中（最大' + MAX_PHOTOS + '枚）';
                    }
                    refreshPhotoPreviews();
                }

                input.addEventListener('change', function () {
                    var incoming = Array.prototype.slice.call(input.files || []);
                    if (incoming.length === 0) return;

                    var slots = MAX_PHOTOS - accumulator.files.length;
                    if (slots <= 0) {
                        alert('写真は最大' + MAX_PHOTOS + '枚までです。');
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
                        if (next.files.length >= MAX_PHOTOS) {
                            skippedForLimit = i < incoming.length;
                            break;
                        }
                        var f = incoming[i];
                        if (!isDuplicate(Array.prototype.slice.call(next.files), f)) {
                            next.items.add(f);
                        }
                    }
                    if (skippedForLimit) {
                        alert('写真は最大' + MAX_PHOTOS + '枚までです。');
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
            }

            function setRowEntryKey(row, key) {
                row.setAttribute('data-entry-key', String(key));
                var re = /entries\[\d+\]/g;
                row.querySelectorAll('[name]').forEach(function (el) {
                    if (el.name) el.name = el.name.replace(re, 'entries[' + key + ']');
                });
                var fid = 'entry-photos-input-' + key;
                var fin = row.querySelector('.entry-photos-input');
                if (fin) {
                    fin.id = fid;
                    var flab = row.querySelector('label.kfc-label[for^="entry-photos-input"]');
                    if (flab) flab.setAttribute('for', fid);
                }
            }

            function addRow() {
                if (rowCount() >= MAX_ROWS) return;
                var proto = container.querySelector('[data-entry-row]');
                if (!proto) return;
                var key = nextKey++;
                form.setAttribute('data-next-entry-key', String(nextKey));
                var row = proto.cloneNode(true);
                setRowEntryKey(row, key);
                row.querySelectorAll('input:not([type="hidden"]), select').forEach(function (el) {
                    if (el.type === 'file') {
                        var nw = el.cloneNode(false);
                        nw.className = el.className;
                        nw.name = el.name;
                        nw.id = 'entry-photos-input-' + key;
                        nw.multiple = true;
                        nw.accept = 'image/*';
                        el.parentNode.replaceChild(nw, el);
                    } else if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                    } else {
                        el.value = '';
                    }
                });
                row.querySelectorAll('.entry-photos-status').forEach(function (el) {
                    el.textContent = 'まだ写真が選択されていません';
                });
                row.querySelectorAll('.entry-photos-preview').forEach(function (el) {
                    el.innerHTML = '';
                    el.classList.add('hidden');
                });
                row.querySelectorAll('.entry-photos-preview-label').forEach(function (el) {
                    el.classList.add('hidden');
                });
                var rm = row.querySelector('.entry-row-remove');
                if (rm) rm.hidden = false;
                container.appendChild(row);
                bindPhotoAccumulator(row);
                refreshRowLabels();
                updateAddButton();
            }

            container.querySelectorAll('[data-entry-row]').forEach(function (row) {
                bindPhotoAccumulator(row);
            });
            refreshRowLabels();
            updateAddButton();

            container.addEventListener('click', function (e) {
                var t = e.target;
                if (!t || !t.closest) return;
                var rm = t.closest('.entry-row-remove');
                if (!rm) return;
                var row = rm.closest('[data-entry-row]');
                if (!row || rowCount() <= 1) return;
                row.remove();
                refreshRowLabels();
                updateAddButton();
            });

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    addRow();
                });
            }
        })();
    </script>
@endpush
