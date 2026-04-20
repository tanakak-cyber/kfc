import exifr from 'exifr';
import piexif from 'piexifjs';

const MAX_EDGE = 1920;
const JPEG_QUALITY = 0.82;

/**
 * @param {string} bin
 * @returns {Blob}
 */
function binaryStringToBlob(bin) {
    const len = bin.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = bin.charCodeAt(i) & 0xff;
    }
    return new Blob([bytes], { type: 'image/jpeg' });
}

/**
 * @param {ArrayBuffer} buffer
 * @returns {string}
 */
function arrayBufferToBinaryString(buffer) {
    const bytes = new Uint8Array(buffer);
    const chunk = 0x8000;
    let binary = '';
    for (let i = 0; i < bytes.length; i += chunk) {
        binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
    }
    return binary;
}

/**
 * @param {Date|string} v
 * @returns {string|null} EXIF "YYYY:MM:DD HH:MM:SS"
 */
function toExifDateTimeOriginal(v) {
    if (v instanceof Date) {
        const p = (n) => String(n).padStart(2, '0');
        return `${v.getFullYear()}:${p(v.getMonth() + 1)}:${p(v.getDate())} ${p(v.getHours())}:${p(v.getMinutes())}:${p(v.getSeconds())}`;
    }
    if (typeof v === 'string' && v.length >= 10) {
        const s = v.trim().replace('T', ' ').replace(/-/g, ':');
        if (/^\d{4}:\d{2}:\d{2} \d{2}:\d{2}:\d{2}$/.test(s)) {
            return s;
        }
    }
    return null;
}

/**
 * @param {File} file
 * @returns {Promise<{ width: number, height: number, draw: (ctx: CanvasRenderingContext2D, dx: number, dy: number, dw: number, dh: number) => void }>}
 */
async function getDrawable(file) {
    if (typeof createImageBitmap === 'function') {
        try {
            const bmp = await createImageBitmap(file, { imageOrientation: 'from-image' });
            return {
                width: bmp.width,
                height: bmp.height,
                draw: (ctx, dx, dy, dw, dh) => ctx.drawImage(bmp, dx, dy, dw, dh),
            };
        } catch {
            /* fall through */
        }
    }
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve({
                width: img.naturalWidth,
                height: img.naturalHeight,
                draw: (ctx, dx, dy, dw, dh) => ctx.drawImage(img, dx, dy, dw, dh),
            });
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('画像の読み込みに失敗しました'));
        };
        img.src = url;
    });
}

/**
 * EXIF DateTimeOriginal を保ったまま JPEG を縮小・圧縮する。
 * 読めない場合は元ファイルを返す（サーバ側 EXIF 検証に任せる）。
 *
 * @param {File} file
 * @returns {Promise<File>}
 */
export async function compressImageFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        return file;
    }

    let dtoStr = null;
    try {
        const meta = await exifr.parse(file);
        const raw = meta?.DateTimeOriginal ?? meta?.DateTimeDigitized;
        dtoStr = toExifDateTimeOriginal(raw);
    } catch {
        /* 元ファイルへフォールバック可能 */
    }

    let drawable;
    try {
        drawable = await getDrawable(file);
    } catch {
        return file;
    }

    let { width: w, height: h } = drawable;
    if (!w || !h) {
        return file;
    }

    let tw = w;
    let th = h;
    if (w > MAX_EDGE || h > MAX_EDGE) {
        if (w >= h) {
            tw = MAX_EDGE;
            th = Math.max(1, Math.round((h * MAX_EDGE) / w));
        } else {
            th = MAX_EDGE;
            tw = Math.max(1, Math.round((w * MAX_EDGE) / h));
        }
    }

    const canvas = document.createElement('canvas');
    canvas.width = tw;
    canvas.height = th;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return file;
    }
    drawable.draw(ctx, 0, 0, tw, th);

    const blob = await new Promise((resolve, reject) => {
        canvas.toBlob(
            (b) => (b ? resolve(b) : reject(new Error('toBlob'))),
            'image/jpeg',
            JPEG_QUALITY
        );
    });

    let ab = await blob.arrayBuffer();
    let bin = arrayBufferToBinaryString(ab);

    if (dtoStr && /^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})$/.test(dtoStr)) {
        try {
            const exifObj = {
                '0th': {
                    [piexif.ImageIFD.DateTime]: dtoStr,
                },
                Exif: {
                    [piexif.ExifIFD.DateTimeOriginal]: dtoStr,
                    [piexif.ExifIFD.DateTimeDigitized]: dtoStr,
                },
            };
            const exifBytes = piexif.dump(exifObj);
            bin = piexif.insert(exifBytes, bin);
        } catch {
            /* EXIF 埋め込み失敗時は無EXIFのJPEGを送る（サーバで弾かれる可能性） */
        }
    }

    const outBlob = binaryStringToBlob(bin);
    const base = (file.name && file.name.replace(/\.[^.]+$/, '')) || 'photo';
    const outName = `${base}.jpg`;

    return new File([outBlob], outName, {
        type: 'image/jpeg',
        lastModified: file.lastModified,
    });
}

function initEntryCatchPhotos() {
    const form = document.getElementById('entry-catch-form');
    const container = document.getElementById('entry-rows-container');
    const addBtn = document.getElementById('entry-add-row-btn');
    if (!form || !container) {
        return;
    }

    const MAX_ROWS = parseInt(form.getAttribute('data-max-entries') || '20', 10);
    const MAX_PHOTOS = 10;
    let nextKey = parseInt(form.getAttribute('data-next-entry-key') || '1', 10);

    function rowCount() {
        return container.querySelectorAll('[data-entry-row]').length;
    }

    function refreshRowLabels() {
        const rows = container.querySelectorAll('[data-entry-row]');
        rows.forEach(function (row, i) {
            const lab = row.querySelector('.entry-catch-row-label');
            if (lab) {
                lab.textContent = String(i + 1);
            }
            const rm = row.querySelector('.entry-row-remove');
            if (rm) {
                rm.hidden = rows.length <= 1;
            }
        });
    }

    function updateAddButton() {
        if (!addBtn) {
            return;
        }
        addBtn.disabled = rowCount() >= MAX_ROWS;
        addBtn.classList.toggle('opacity-50', addBtn.disabled);
    }

    function bindPhotoAccumulator(row) {
        const input = row.querySelector('.entry-photos-input');
        const statusEl = row.querySelector('.entry-photos-status');
        const previewEl = row.querySelector('.entry-photos-preview');
        const previewLabel = row.querySelector('.entry-photos-preview-label');
        const clearBtn = row.querySelector('.entry-photos-clear');
        if (!input || !input.multiple) {
            return;
        }

        let accumulator = new DataTransfer();
        const previewObjectUrls = [];

        function isDuplicate(list, file) {
            for (let i = 0; i < list.length; i++) {
                const x = list[i];
                if (x.name === file.name && x.size === file.size && x.lastModified === file.lastModified) {
                    return true;
                }
            }
            return false;
        }

        function revokePreviewUrls() {
            previewObjectUrls.forEach(function (u) {
                URL.revokeObjectURL(u);
            });
            previewObjectUrls.length = 0;
        }

        function refreshPhotoPreviews() {
            revokePreviewUrls();
            if (!previewEl) {
                return;
            }
            previewEl.innerHTML = '';
            const n = accumulator.files.length;
            if (n === 0) {
                previewEl.classList.add('hidden');
                if (previewLabel) {
                    previewLabel.classList.add('hidden');
                }
                return;
            }
            previewEl.classList.remove('hidden');
            if (previewLabel) {
                previewLabel.classList.remove('hidden');
            }
            for (let i = 0; i < n; i++) {
                const file = accumulator.files[i];
                const url = URL.createObjectURL(file);
                previewObjectUrls.push(url);
                const li = document.createElement('li');
                li.className = 'flex min-w-0 w-24 shrink-0 flex-col sm:w-28';
                const frame = document.createElement('div');
                frame.className =
                    'h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 shadow-sm sm:h-28 sm:w-28';
                const img = document.createElement('img');
                img.src = url;
                img.alt = '';
                img.className = 'block h-full w-full max-h-full max-w-full object-cover';
                img.loading = 'lazy';
                frame.appendChild(img);
                const cap = document.createElement('p');
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
                const n = accumulator.files.length;
                statusEl.textContent =
                    n === 0 ? 'まだ写真が選択されていません' : n + '枚選択中（最大' + MAX_PHOTOS + '枚）';
            }
            refreshPhotoPreviews();
        }

        input.addEventListener('change', async function () {
            const incoming = Array.prototype.slice.call(input.files || []);
            if (incoming.length === 0) {
                return;
            }

            let slots = MAX_PHOTOS - accumulator.files.length;
            if (slots <= 0) {
                alert('写真は最大' + MAX_PHOTOS + '枚までです。');
                applyToInput();
                return;
            }

            if (statusEl) {
                statusEl.textContent = '写真を圧縮しています…';
            }

            const processed = [];
            for (let i = 0; i < incoming.length && processed.length < slots; i++) {
                try {
                    processed.push(await compressImageFile(incoming[i]));
                } catch {
                    processed.push(incoming[i]);
                }
            }

            const next = new DataTransfer();
            let j;
            for (j = 0; j < accumulator.files.length; j++) {
                next.items.add(accumulator.files[j]);
            }
            let skippedForLimit = false;
            for (j = 0; j < processed.length; j++) {
                if (next.files.length >= MAX_PHOTOS) {
                    skippedForLimit = j < processed.length - 1;
                    break;
                }
                const f = processed[j];
                if (!isDuplicate(Array.prototype.slice.call(next.files), f)) {
                    next.items.add(f);
                }
            }
            if (skippedForLimit) {
                alert('写真は最大' + MAX_PHOTOS + '枚までです。');
            }

            accumulator = next;
            input.value = '';
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
        const re = /entries\[\d+\]/g;
        row.querySelectorAll('[name]').forEach(function (el) {
            if (el.name) {
                el.name = el.name.replace(re, 'entries[' + key + ']');
            }
        });
        const fid = 'entry-photos-input-' + key;
        const fin = row.querySelector('.entry-photos-input');
        if (fin) {
            fin.id = fid;
            const flab = row.querySelector('label.kfc-label[for^="entry-photos-input"]');
            if (flab) {
                flab.setAttribute('for', fid);
            }
        }
    }

    function addRow() {
        if (rowCount() >= MAX_ROWS) {
            return;
        }
        const proto = container.querySelector('[data-entry-row]');
        if (!proto) {
            return;
        }
        const key = nextKey++;
        form.setAttribute('data-next-entry-key', String(nextKey));
        const row = proto.cloneNode(true);
        setRowEntryKey(row, key);
        row.querySelectorAll('input:not([type="hidden"]), select').forEach(function (el) {
            if (el.type === 'file') {
                const nw = el.cloneNode(false);
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
        const rm = row.querySelector('.entry-row-remove');
        if (rm) {
            rm.hidden = false;
        }
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
        const t = e.target;
        if (!t || !t.closest) {
            return;
        }
        const rm = t.closest('.entry-row-remove');
        if (!rm) {
            return;
        }
        const row = rm.closest('[data-entry-row]');
        if (!row || rowCount() <= 1) {
            return;
        }
        row.remove();
        refreshRowLabels();
        updateAddButton();
    });

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            addRow();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntryCatchPhotos);
} else {
    initEntryCatchPhotos();
}
