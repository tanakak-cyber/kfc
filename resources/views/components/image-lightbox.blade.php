@once('kfc-image-lightbox')
<div
    id="kfc-image-lightbox"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-6"
    role="dialog"
    aria-modal="true"
    aria-label="画像拡大"
>
    <button
        type="button"
        class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-xl font-semibold text-slate-800 shadow hover:bg-white"
        onclick="window.kfcCloseImageLightbox()"
        aria-label="閉じる"
    >&times;</button>
    <img
        id="kfc-image-lightbox-img"
        src=""
        alt=""
        class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl ring-2 ring-white/20"
    />
</div>
<script>
(function () {
    function root() { return document.getElementById('kfc-image-lightbox'); }
    function img() { return document.getElementById('kfc-image-lightbox-img'); }
    window.kfcOpenImageLightbox = function (src) {
        var r = root(), i = img();
        if (!r || !i || !src) return;
        i.src = src;
        r.classList.remove('hidden');
        r.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };
    window.kfcCloseImageLightbox = function () {
        var r = root(), i = img();
        if (!r || !i) return;
        r.classList.add('hidden');
        r.classList.remove('flex');
        i.removeAttribute('src');
        document.body.classList.remove('overflow-hidden');
    };
    document.addEventListener('DOMContentLoaded', function () {
        var r = root();
        if (r) {
            r.addEventListener('click', function (e) {
                if (e.target === r) window.kfcCloseImageLightbox();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.kfcCloseImageLightbox();
        });
    });
})();
</script>
@endonce
