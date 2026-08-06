const applyStatus = (row, data, labels) => {
    row.dataset.presentationId = data.id ?? row.dataset.presentationId;
    const label = row.querySelector('[data-presentation-status-label]');
    label.textContent = labels[data.status] ?? data.status;
    label.className = `status-pill status-${data.status}`;
    const preview = row.querySelector('[data-presentation-preview]');
    if (data.status === 'done' && data.pdf_url) {
        preview.href = data.pdf_url;
        preview.hidden = false;
    }
    const error = row.querySelector('[data-presentation-error]');
    if (data.status === 'failed' && data.error_message) {
        error.textContent = data.error_message;
        error.hidden = false;
    }
    if (data.status === 'done' || data.status === 'failed') row.dataset.pollDone = '1';
};

export const pollPresentation = (body, list, row, id, labels) => {
    const url = list.dataset.statusUrl.replace('__ID__', id);
    const tick = () => {
        if (! body.contains(row) || row.dataset.pollDone) return;
        fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => { applyStatus(row, data, labels); if (! row.dataset.pollDone) window.setTimeout(tick, 3000); })
            .catch(() => window.setTimeout(tick, 5000));
    };
    tick();
};
