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
                                    <p class="mt-2 text-xs text-zinc-500">1匹あたり1〜10枚。スマホでは「もう一度ファイルを選ぶ」と枚数が<strong>足されていきます</strong>。選択後、送信前にブラウザで縮小・JPEG圧縮します（撮影日時の情報は引き継ぎます）。</p>
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
    @vite(['resources/js/entry-catch-photos.js'])
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
@endpush
