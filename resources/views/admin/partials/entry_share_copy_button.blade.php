{{--
  @var \App\Models\GameMatch $gameMatch
  @var string $entryUrl 釣果投稿の絶対URL
  @var string|null $entryMailAction POST 先（未指定ならメールボタンは出さない）
--}}
@php
    $shareFirstLine = $gameMatch->start_datetime
        ? $gameMatch->start_datetime->format('n') . '月' . $gameMatch->start_datetime->format('j') . '日の釣果投稿は以下のURLからお願いします。'
        : '本試合の釣果投稿は以下のURLからお願いします。';
    $matchPublicUrl = route('matches.show', $gameMatch, absolute: true);
@endphp

<div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
    <button
        type="button"
        class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-100"
        data-kfc-entry-share-copy
        data-share-line1="{{ e($shareFirstLine) }}"
        data-share-entry="{{ e($entryUrl) }}"
        data-share-match="{{ e($matchPublicUrl) }}"
    >
        共有文をコピー
    </button>
    @isset($entryMailAction)
        <form method="post" action="{{ $entryMailAction }}" class="inline" onsubmit="return confirm('釣果投稿URLをメールで送信しますか？');">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-900 shadow-sm transition hover:bg-sky-100"
            >
                メール送信
            </button>
        </form>
    @endisset
    <span class="kfc-entry-share-copy-feedback hidden text-xs font-medium text-emerald-800" aria-live="polite"></span>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function buildShareBody(line1, entryUrl, matchUrl) {
                    return (
                        '-----------------------------------\n' +
                        line1 +
                        '\n' +
                        entryUrl +
                        '\n※投稿は試合時間内しかできません。\n\n' +
                        'なお、本試合のルールについては以下のURLから確認してください。\n' +
                        matchUrl +
                        '\n-----------------------------------'
                    );
                }

                function showFeedback(btn, ok) {
                    var wrap = btn.closest('div');
                    var el = wrap ? wrap.querySelector('.kfc-entry-share-copy-feedback') : null;
                    if (!el) return;
                    el.textContent = ok ? 'コピーしました' : 'コピーに失敗しました';
                    el.classList.remove('hidden');
                    el.classList.toggle('text-red-700', !ok);
                    el.classList.toggle('text-emerald-800', ok);
                    clearTimeout(el._kfcT);
                    el._kfcT = setTimeout(function () {
                        el.classList.add('hidden');
                        el.textContent = '';
                    }, 2500);
                }

                function copyText(text, btn) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(
                            function () {
                                showFeedback(btn, true);
                            },
                            function () {
                                fallbackCopy(text, btn);
                            }
                        );
                        return;
                    }
                    fallbackCopy(text, btn);
                }

                function fallbackCopy(text, btn) {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                        var ok = document.execCommand('copy');
                        showFeedback(btn, ok);
                    } catch (e) {
                        showFeedback(btn, false);
                    }
                    document.body.removeChild(ta);
                }

                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-kfc-entry-share-copy]');
                    if (!btn) return;
                    e.preventDefault();
                    var line1 = btn.getAttribute('data-share-line1') || '';
                    var entry = btn.getAttribute('data-share-entry') || '';
                    var match = btn.getAttribute('data-share-match') || '';
                    copyText(buildShareBody(line1, entry, match), btn);
                });
            })();
        </script>
    @endpush
@endonce
