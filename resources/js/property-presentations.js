const dialog = document.querySelector('[data-presentation-dialog]');
if (dialog) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const body = dialog.querySelector('[data-dialog-body]');
    const statusLabels = { queued: 'En cola', processing: 'Generando…', done: 'Lista', failed: 'Error' };

    // Todo lo de adentro se reemplaza en cada fetch, así que los listeners van
    // delegados en el <dialog> (estable) en vez de sobre nodos específicos.
    const showView = (name) => body.querySelectorAll('[data-panel-view]')
        .forEach((view) => { view.hidden = view.dataset.panelView !== name; });

    const applyStatus = (row, data) => {
        row.dataset.presentationId = data.id ?? row.dataset.presentationId;
        const label = row.querySelector('[data-presentation-status-label]');
        label.textContent = statusLabels[data.status] ?? data.status;
        label.className = `status-pill status-${data.status}`;
        const preview = row.querySelector('[data-presentation-preview]');
        if (data.status === 'done' && data.pdf_url) {
            preview.href = data.pdf_url;
            preview.hidden = false;
        }
        const errorEl = row.querySelector('[data-presentation-error]');
        if (data.status === 'failed' && data.error_message) {
            errorEl.textContent = data.error_message;
            errorEl.hidden = false;
        }
        if (data.status === 'done' || data.status === 'failed') row.dataset.pollDone = '1';
    };

    const poll = (list, row, id) => {
        const url = list.dataset.statusUrl.replace('__ID__', id);
        const tick = () => {
            if (! body.contains(row) || row.dataset.pollDone) return;
            fetch(url, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => { applyStatus(row, data); if (! row.dataset.pollDone) window.setTimeout(tick, 3000); })
                .catch(() => window.setTimeout(tick, 5000));
        };
        tick();
    };

    const syncFaqIndexes = (list) => [...list.children].forEach((row, index) => row.querySelectorAll('[name]')
        .forEach((input) => input.name = input.name.replace(/faq_manual\[(?:\d+|__INDEX__)\]/, `faq_manual[${index}]`)));

    const syncReveals = (select) => {
        const key = select.dataset.reveals;
        body.querySelectorAll(`[data-reveal-target="${key}"]`).forEach((target) => { target.hidden = select.value !== 'manual'; });
    };

    const loadPanel = (url) => {
        showView(null);
        body.innerHTML = '<p class="document-empty">Cargando…</p>';
        dialog.showModal();
        fetch(url, { headers: { Accept: 'text/html' } })
            .then((response) => response.text())
            .then((html) => {
                body.innerHTML = html;
                body.querySelectorAll('[data-mode-select]').forEach(syncReveals);
                body.querySelectorAll('[data-presentation-list] [data-presentation-row]:not([data-poll-done])').forEach((row) => {
                    if (row.dataset.presentationId) poll(body.querySelector('[data-presentation-list]'), row, row.dataset.presentationId);
                });
            })
            .catch(() => { body.innerHTML = '<p class="document-empty">No se pudo cargar. Intenta de nuevo.</p>'; });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-presentation-dialog]');
        if (trigger) loadPanel(trigger.dataset.panelUrl);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-presentation-modal]')) dialog.close();
        if (event.target.closest('[data-show-generate-form]')) showView('form');
        if (event.target.closest('[data-show-list-view]')) showView('list');

        const faqAdd = event.target.closest('[data-faq-add]');
        if (faqAdd) {
            const list = body.querySelector('[data-faq-list]');
            const template = body.querySelector('[data-faq-template]');
            list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', list.children.length));
            list.lastElementChild.querySelector('input').focus();
            syncFaqIndexes(list);
        }
        const faqRemove = event.target.closest('[data-faq-remove]');
        if (faqRemove) {
            const list = body.querySelector('[data-faq-list]');
            faqRemove.closest('.youtube-editor-row').remove();
            syncFaqIndexes(list);
        }
    });

    dialog.addEventListener('change', (event) => {
        if (event.target.matches('[data-images-mode]')) {
            body.querySelector('[data-manual-images]').hidden = event.target.form
                .querySelector('[data-images-mode]:checked').value !== 'manual';
        }
        if (event.target.matches('[data-mode-select]')) syncReveals(event.target);
    });

    dialog.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-presentation-form]');
        if (! form) return;
        event.preventDefault();

        const submitButton = form.querySelector('[data-presentation-submit]');
        const formError = form.querySelector('[data-presentation-form-error]');
        submitButton.disabled = true;
        formError.hidden = true;

        fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: new FormData(form),
        })
            .then(async (response) => {
                const data = await response.json();
                if (! response.ok) throw data;

                const list = body.querySelector('[data-presentation-list]');
                const rowTemplate = body.querySelector('[data-presentation-row-template]');
                const row = rowTemplate.content.firstElementChild.cloneNode(true);
                row.dataset.presentationId = data.id;
                row.querySelector('strong').textContent = form.querySelector('input[name=template_key]:checked')
                    ?.closest('.template-option').querySelector('strong').textContent ?? 'Presentación';
                body.querySelector('[data-presentation-empty]')?.setAttribute('hidden', '');
                list.prepend(row);
                poll(list, row, data.id);

                showView('list');
                form.reset();
            })
            .catch((error) => {
                formError.textContent = error?.message
                    ?? Object.values(error?.errors ?? {}).flat()[0]
                    ?? 'No se pudo iniciar la generación. Intenta de nuevo.';
                formError.hidden = false;
            })
            .finally(() => { submitButton.disabled = false; });
    });
}
