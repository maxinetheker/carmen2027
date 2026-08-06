const panel = document.querySelector('[data-presentations-panel]');
const modal = document.querySelector('[data-presentation-modal]');
if (panel && modal) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const list = panel.querySelector('[data-presentation-list]');
    const empty = panel.querySelector('[data-presentation-empty]');
    const rowTemplate = panel.querySelector('[data-presentation-row-template]');
    const statusUrlBase = panel.dataset.statusUrl;
    const form = modal.querySelector('[data-presentation-form]');
    const submitButton = form.querySelector('[data-presentation-submit]');
    const formError = form.querySelector('[data-presentation-form-error]');

    document.querySelector('[data-open-presentation-modal]')?.addEventListener('click', () => modal.showModal());
    modal.querySelector('[data-close-presentation-modal]')?.addEventListener('click', () => modal.close());

    // Modo de imágenes: mostrar la grilla manual solo si corresponde.
    const manualImages = form.querySelector('[data-manual-images]');
    form.querySelectorAll('[data-images-mode]').forEach((radio) => {
        radio.addEventListener('change', () => {
            manualImages.hidden = form.querySelector('[data-images-mode]:checked').value !== 'manual';
        });
    });

    // Selects de 3 vías (Automático/Manual/Desactivado) que revelan un campo cuando es "manual".
    form.querySelectorAll('[data-mode-select]').forEach((select) => {
        const key = select.dataset.reveals;
        const targets = form.querySelectorAll(`[data-reveal-target="${key}"]`);
        const sync = () => targets.forEach((target) => { target.hidden = select.value !== 'manual'; });
        select.addEventListener('change', sync);
        sync();
    });

    // Repetidor de preguntas frecuentes manuales (mismo patrón que youtube-videos.js).
    const faqList = form.querySelector('[data-faq-list]');
    const faqTemplate = form.querySelector('[data-faq-template]');
    const faqAdd = form.querySelector('[data-faq-add]');
    const syncFaqIndexes = () => [...faqList.children].forEach((row, index) => row.querySelectorAll('[name]')
        .forEach((input) => input.name = input.name.replace(/faq_manual\[(?:\d+|__INDEX__)\]/, `faq_manual[${index}]`)));
    faqAdd?.addEventListener('click', () => {
        faqList.insertAdjacentHTML('beforeend', faqTemplate.innerHTML.replaceAll('__INDEX__', faqList.children.length));
        faqList.lastElementChild.querySelector('input').focus();
        syncFaqIndexes();
    });
    faqList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-faq-remove]');
        if (! button) return;
        button.closest('.youtube-editor-row').remove();
        syncFaqIndexes();
    });

    const statusLabels = { queued: 'En cola', processing: 'Generando…', done: 'Lista', failed: 'Error' };

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
        if (data.status === 'done' || data.status === 'failed') {
            row.dataset.pollDone = '1';
        }
    };

    const poll = (row, id) => {
        const url = statusUrlBase.replace('__ID__', id);
        const tick = () => {
            if (row.dataset.pollDone) return;
            fetch(url, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    applyStatus(row, data);
                    if (! row.dataset.pollDone) window.setTimeout(tick, 3000);
                })
                .catch(() => window.setTimeout(tick, 5000));
        };
        tick();
    };

    // Reanudar el sondeo de presentaciones que quedaron "en cola"/"generando" al recargar la página.
    list.querySelectorAll('[data-presentation-row]:not([data-poll-done])').forEach((row) => {
        if (row.dataset.presentationId) poll(row, row.dataset.presentationId);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
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

                empty?.setAttribute('hidden', '');
                const row = rowTemplate.content.firstElementChild.cloneNode(true);
                row.dataset.presentationId = data.id;
                row.querySelector('strong').textContent = form.querySelector('input[name=template_key]:checked')
                    ?.closest('.template-option').querySelector('strong').textContent ?? 'Presentación';
                list.prepend(row);
                poll(row, data.id);

                modal.close();
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
