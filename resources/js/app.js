import './bootstrap';

/** 釣果スライダー: ‹ › で横スクロール。拡大は data-kfc-lightbox + window.kfcCatchGalleries（Blade で登録） */
document.addEventListener('click', (e) => {
    const prevBtn = e.target.closest('[data-kfc-slider-prev]');
    if (prevBtn) {
        const id = prevBtn.getAttribute('data-kfc-slider-target');
        const track = id ? document.getElementById(id) : null;
        if (track) {
            track.scrollBy({ left: -track.clientWidth, behavior: 'smooth' });
        }
        e.preventDefault();
        return;
    }

    const nextBtn = e.target.closest('[data-kfc-slider-next]');
    if (nextBtn) {
        const id = nextBtn.getAttribute('data-kfc-slider-target');
        const track = id ? document.getElementById(id) : null;
        if (track) {
            track.scrollBy({ left: track.clientWidth, behavior: 'smooth' });
        }
        e.preventDefault();
        return;
    }

    const lightboxBtn = e.target.closest('[data-kfc-lightbox]');
    if (lightboxBtn) {
        const key = lightboxBtn.getAttribute('data-kfc-lightbox');
        const idx = parseInt(lightboxBtn.getAttribute('data-kfc-lightbox-index') || '0', 10);
        const urls = key && window.kfcCatchGalleries ? window.kfcCatchGalleries[key] : null;
        if (Array.isArray(urls) && urls.length > 0 && typeof window.kfcOpenImageGallery === 'function') {
            window.kfcOpenImageGallery(urls, idx);
        }
        e.preventDefault();
    }
});
