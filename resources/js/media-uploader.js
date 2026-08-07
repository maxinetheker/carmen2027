/**
 * Uploads gallery files one request at a time.
 *
 * Posting every file in the single form submit meant a big gallery quietly hit PHP's
 * max_file_uploads / post_max_size and lost the surplus. One request per file has no
 * such ceiling, keeps the page responsive, and lets a single bad file report its own
 * error next to its thumbnail instead of failing the whole property save.
 */
const escape = (value) => String(value ?? '').replace(/[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const cardMarkup = (media) => `
    <button class="drag-handle" type="button" aria-label="Arrastrar para ordenar">
        <span class="material-symbols-rounded">drag_indicator</span>
    </button>
    ${media.type === 'image'
        ? `<img src="${escape(media.url)}" alt="">`
        : `<video src="${escape(media.url)}" muted preload="metadata" playsinline></video>`}
    <strong>${escape(media.name ?? '')}</strong>
    ${media.type === 'image'
        ? `<label><input type="radio" name="cover_media_id" value="${media.id}"${media.is_cover ? ' checked' : ''}> Usar como principal</label>`
        : ''}
    <label class="remove-media">
        <input type="checkbox" name="remove_media[]" value="${media.id}"> Eliminar al guardar
    </label>`;

const pendingMarkup = (file) => `
    <span class="media-upload-name">${escape(file.name)}</span>
    <span class="media-upload-bar"><i style="width:0%"></i></span>
    <span class="media-upload-state">Subiendo…</span>`;

const failedMarkup = (file, message) => `
    <span class="media-upload-name">${escape(file.name)}</span>
    <p class="media-upload-error">${escape(message)}</p>
    <button class="mini-button" type="button" data-media-retry>Reintentar</button>
    <button class="mini-button mini-button-muted" type="button" data-media-discard>Descartar</button>`;

const messageFrom = (xhr) => {
    if (xhr.status === 413) return 'El servidor rechazó el archivo por tamaño (413).';
    if (xhr.status === 419) return 'La sesión expiró. Recarga la página e inténtalo de nuevo.';
    if (! xhr.status) return 'Sin conexión con el servidor.';
    try {
        const body = JSON.parse(xhr.responseText);
        return body.message ?? Object.values(body.errors ?? {}).flat()[0] ?? `Error ${xhr.status}.`;
    } catch {
        return `Error ${xhr.status} al subir el archivo.`;
    }
};

export function createMediaUploader({ endpoint, csrf, list, onChange, onNotice }) {
    let active = 0;

    const send = (file, card) => {
        active += 1;
        card.className = 'media-upload-card';
        card.innerHTML = pendingMarkup(file);
        const fill = card.querySelector('.media-upload-bar i');
        const state = card.querySelector('.media-upload-state');
        const data = new FormData();
        data.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
            if (! event.lengthComputable) return;
            const percent = Math.round((event.loaded / event.total) * 100);
            fill.style.width = `${percent}%`;
            state.textContent = percent < 100 ? `Subiendo… ${percent}%` : 'Procesando…';
        });
        xhr.addEventListener('loadend', () => {
            active -= 1;
            if (xhr.status === 201) {
                const media = JSON.parse(xhr.responseText);
                card.className = '';
                card.draggable = true;
                card.dataset.mediaCard = '';
                card.dataset.mediaToken = `existing:${media.id}`;
                card.innerHTML = cardMarkup(media);
                onChange();
            } else {
                card.className = 'media-upload-card media-upload-failed';
                card.innerHTML = failedMarkup(file, messageFrom(xhr));
                card._retry = () => send(file, card);
                onNotice(`No se pudo subir «${file.name}».`, 'error');
            }
            if (active === 0) onNotice(null);
        });
        xhr.send(data);
    };

    list.addEventListener('click', (event) => {
        const card = event.target.closest('.media-upload-failed');
        if (! card) return;
        if (event.target.closest('[data-media-retry]')) card._retry?.();
        if (event.target.closest('[data-media-discard]')) { card.remove(); onChange(); }
    });

    return {
        add(files) {
            const accepted = [...files].filter((file) => file.type.startsWith('image/')
                || file.type.startsWith('video/'));
            const rejected = [...files].length - accepted.length;
            if (rejected) onNotice(`${rejected} archivo(s) ignorado(s): solo se admiten fotos y videos.`, 'error');
            accepted.forEach((file) => {
                const card = document.createElement('article');
                list.append(card);
                send(file, card);
            });
            if (accepted.length) onNotice(`Subiendo ${accepted.length} archivo(s)…`);
        },
    };
}
