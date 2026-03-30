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

/** 管理画面: スマホはハンバーガーでサイドメニュー開閉 */
(function initAdminMobileNav() {
    const toggle = document.getElementById('kfc-admin-menu-toggle');
    const sidebar = document.getElementById('kfc-admin-sidebar');
    const overlay = document.getElementById('kfc-admin-overlay');
    if (!toggle || !sidebar || !overlay) {
        return;
    }

    const iconMenu = toggle.querySelector('[data-kfc-admin-icon-menu]');
    const iconClose = toggle.querySelector('[data-kfc-admin-icon-close]');
    const mq = window.matchMedia('(min-width: 768px)');

    function setIconsOpen(open) {
        if (iconMenu) {
            iconMenu.classList.toggle('hidden', open);
        }
        if (iconClose) {
            iconClose.classList.toggle('hidden', !open);
        }
    }

    function setOpen(open) {
        if (mq.matches) {
            return;
        }
        if (open) {
            sidebar.classList.add('kfc-admin-sidebar--open');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', '管理メニューを閉じる');
        } else {
            sidebar.classList.remove('kfc-admin-sidebar--open');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', '管理メニューを開く');
        }
        setIconsOpen(open);
    }

    function closeIfMobile() {
        if (!mq.matches) {
            setOpen(false);
        }
    }

    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        setOpen(open);
    });

    overlay.addEventListener('click', () => setOpen(false));

    mq.addEventListener('change', (e) => {
        if (e.matches) {
            sidebar.classList.remove('kfc-admin-sidebar--open');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', '管理メニューを開く');
            setIconsOpen(false);
        } else {
            sidebar.classList.remove('kfc-admin-sidebar--open');
        }
    });

    sidebar.querySelectorAll('a').forEach((a) => {
        a.addEventListener('click', closeIfMobile);
    });
    sidebar.querySelectorAll('form').forEach((f) => {
        f.addEventListener('submit', closeIfMobile);
    });
})();
