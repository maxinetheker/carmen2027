const STATUS_LABELS = { queued: 'En cola', processing: 'Generando…', done: 'Lista', failed: 'Error' };

const applyStatus = (row, data) => {
    const label = row.querySelector('[data-social-status-label]');
    label.textContent = STATUS_LABELS[data.status] ?? data.status;
    label.className = `status-pill status-${data.status}`;

    if (data.status === 'done' && data.image_url) {
        const preview = row.querySelector('[data-social-preview]');
        preview.href = data.image_url;
        preview.querySelector('img').src = data.image_url;
        preview.hidden = false;
        const download = row.querySelector('[data-social-download]');
        download.href = data.image_url;
        download.hidden = false;
    }

    const error = row.querySelector('[data-social-error]');
    if (data.status === 'failed' && data.error_message) {
        error.textContent = data.error_message;
        error.hidden = false;
    }

    const warning = row.querySelector('[data-social-warning]');
    if (warning && data.warnings?.length) {
        warning.textContent = data.warnings.join(' ');
        warning.hidden = false;
    }

    if (data.status === 'done' || data.status === 'failed') row.dataset.pollDone = '1';
};

export const pollSocialImage = (body, row, id) => {
    const url = body.querySelector('[data-social-list]').dataset.statusUrl.replace('__ID__', id);
    // Image generation runs longer than the PDF, so this polls more slowly.
    const tick = () => {
        if (! body.contains(row) || row.dataset.pollDone) return;
        fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => { applyStatus(row, data); if (! row.dataset.pollDone) window.setTimeout(tick, 5000); })
            .catch(() => window.setTimeout(tick, 8000));
    };
    tick();
};
