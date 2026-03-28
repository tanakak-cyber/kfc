@once('kfc-image-lightbox')
<div
    id="kfc-image-lightbox"
    class="fixed inset-0 z-[100] hidden items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-label="画像ギャラリー"
>
    <button
        type="button"
        class="absolute inset-0 z-0 cursor-default bg-black/60 backdrop-blur-sm"
        onclick="window.kfcCloseImageLightbox()"
        aria-label="閉じる"
    ></button>
    <button
        type="button"
        class="absolute right-3 top-3 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-xl font-semibold text-zinc-800 shadow-lg ring-1 ring-zinc-200/80 transition hover:bg-white sm:right-4 sm:top-4"
        onclick="window.kfcCloseImageLightbox()"
        aria-label="閉じる"
    >&times;</button>
    <button
        type="button"
        id="kfc-image-lightbox-prev"
        class="absolute left-2 top-1/2 z-20 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-2xl font-bold text-zinc-800 shadow-lg ring-1 ring-zinc-200/80 transition hover:bg-white sm:left-4 sm:h-14 sm:w-14"
        onclick="event.stopPropagation(); window.kfcImageLightboxPrev();"
        aria-label="前の画像"
    >‹</button>
    <button
        type="button"
        id="kfc-image-lightbox-next"
        class="absolute right-2 top-1/2 z-20 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-2xl font-bold text-zinc-800 shadow-lg ring-1 ring-zinc-200/80 transition hover:bg-white sm:right-4 sm:h-14 sm:w-14"
        onclick="event.stopPropagation(); window.kfcImageLightboxNext();"
        aria-label="次の画像"
    >›</button>
    <p id="kfc-image-lightbox-counter" class="pointer-events-none absolute bottom-3 left-0 right-0 z-20 hidden text-center text-sm font-medium text-zinc-200 sm:bottom-4"></p>
    <div
        id="kfc-image-lightbox-stage"
        class="relative z-10 flex max-h-[min(90vh,100%)] w-full max-w-5xl touch-pan-y items-center justify-center px-2 sm:px-14"
        onclick="event.stopPropagation()"
    >
        <img
            id="kfc-image-lightbox-img"
            src=""
            alt=""
            class="max-h-[85vh] max-w-full rounded-2xl object-contain shadow-2xl ring-2 ring-white/30 select-none"
            draggable="false"
        />
    </div>
</div>
<script>
(function () {
    var root = function () { return document.getElementById('kfc-image-lightbox'); };
    var stage = function () { return document.getElementById('kfc-image-lightbox-stage'); };
    var img = function () { return document.getElementById('kfc-image-lightbox-img'); };
    var btnPrev = function () { return document.getElementById('kfc-image-lightbox-prev'); };
    var btnNext = function () { return document.getElementById('kfc-image-lightbox-next'); };
    var counter = function () { return document.getElementById('kfc-image-lightbox-counter'); };

    var state = { urls: [], index: 0, open: false };
    var touchStartX = null;

    function updateUi() {
        var urls = state.urls;
        var i = img();
        var n = urls.length;
        if (!i || n === 0) return;
        i.src = urls[state.index] || '';

        var multi = n > 1;
        var bp = btnPrev();
        var bn = btnNext();
        var c = counter();
        if (bp) {
            bp.classList.toggle('hidden', !multi);
            bp.classList.toggle('flex', multi);
        }
        if (bn) {
            bn.classList.toggle('hidden', !multi);
            bn.classList.toggle('flex', multi);
        }
        if (c) {
            if (multi) {
                c.classList.remove('hidden');
                c.textContent = (state.index + 1) + ' / ' + n;
            } else {
                c.classList.add('hidden');
                c.textContent = '';
            }
        }
    }

    function onKeyDown(e) {
        if (!state.open) return;
        if (e.key === 'Escape') {
            window.kfcCloseImageLightbox();
            return;
        }
        if (state.urls.length < 2) return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            window.kfcImageLightboxPrev();
        }
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            window.kfcImageLightboxNext();
        }
    }

    window.kfcOpenImageGallery = function (urls, startIndex) {
        if (!urls || !urls.length) return;
        if (!Array.isArray(urls)) {
            urls = [urls];
        }
        var r = root();
        var i = img();
        if (!r || !i) return;
        state.urls = urls.slice();
        var idx = typeof startIndex === 'number' ? startIndex : parseInt(startIndex, 10);
        if (isNaN(idx)) idx = 0;
        state.index = Math.max(0, Math.min(idx, state.urls.length - 1));
        state.open = true;
        updateUi();
        r.classList.remove('hidden');
        r.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    window.kfcOpenImageLightbox = function (srcOrUrls, startIndex) {
        if (Array.isArray(srcOrUrls)) {
            var idx = typeof startIndex === 'number' ? startIndex : parseInt(startIndex, 10);
            if (isNaN(idx)) idx = 0;
            window.kfcOpenImageGallery(srcOrUrls, idx);
        } else if (srcOrUrls) {
            window.kfcOpenImageGallery([srcOrUrls], 0);
        }
    };

    window.kfcCloseImageLightbox = function () {
        var r = root();
        var i = img();
        if (!r || !i) return;
        state.open = false;
        state.urls = [];
        state.index = 0;
        i.removeAttribute('src');
        r.classList.add('hidden');
        r.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        var c = counter();
        if (c) {
            c.classList.add('hidden');
            c.textContent = '';
        }
    };

    window.kfcImageLightboxPrev = function () {
        if (state.urls.length < 2) return;
        state.index = (state.index - 1 + state.urls.length) % state.urls.length;
        updateUi();
    };

    window.kfcImageLightboxNext = function () {
        if (state.urls.length < 2) return;
        state.index = (state.index + 1) % state.urls.length;
        updateUi();
    };

    document.addEventListener('DOMContentLoaded', function () {
        var st = stage();
        if (st) {
            st.addEventListener('touchstart', function (e) {
                if (!state.open || state.urls.length < 2) return;
                if (e.changedTouches.length) touchStartX = e.changedTouches[0].clientX;
            }, { passive: true });
            st.addEventListener('touchend', function (e) {
                if (touchStartX === null || !state.open || state.urls.length < 2) return;
                if (!e.changedTouches.length) return;
                var dx = e.changedTouches[0].clientX - touchStartX;
                touchStartX = null;
                if (dx > 56) window.kfcImageLightboxPrev();
                else if (dx < -56) window.kfcImageLightboxNext();
            }, { passive: true });
        }
        document.addEventListener('keydown', onKeyDown);
    });
})();
</script>
@endonce
