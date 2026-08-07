import { captureCroquisMap, clearCroquisCapture } from './croquis-capture';
import { handlePresentationFormChange, syncPresentationForm } from './presentation-form-controls';
import { pollPresentation } from './presentation-polling';

const dialog = document.querySelector('[data-presentation-dialog]');
if (dialog) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const body = dialog.querySelector('[data-dialog-body]');
    const statusLabels = { queued: 'En cola', processing: 'Generando…', done: 'Lista', failed: 'Error' };
    let panelUrl = null;

    const showView = (name) => body.querySelectorAll('[data-panel-view]')
        .forEach((view) => { view.hidden = view.dataset.panelView !== name; });
    const syncFaqIndexes = (list) => [...list.children].forEach((row, index) => row.querySelectorAll('[name]')
        .forEach((input) => input.name = input.name.replace(/faq_manual\[(?:\d+|__INDEX__)\]/, `faq_manual[${index}]`)));

    const loadPanel = (url, view = 'list') => {
        panelUrl = url;
        body.innerHTML = '<p class="document-empty">Cargando…</p>';
        dialog.showModal();
        fetch(url, { headers: { Accept: 'text/html' } })
            .then((response) => response.text())
            .then((html) => {
                body.innerHTML = html;
                syncPresentationForm(body);
                showView(view);
                body.querySelectorAll('[data-presentation-list] [data-presentation-row]:not([data-poll-done])').forEach((row) => {
                    if (row.dataset.presentationId) {
                        pollPresentation(body, body.querySelector('[data-presentation-list]'), row, row.dataset.presentationId, statusLabels);
                    }
                });
            })
            .catch(() => { body.innerHTML = '<p class="document-empty">No se pudo cargar. Intenta de nuevo.</p>'; });
    };

    const updateDocuments = (form) => {
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        return fetch(form.action, {
            method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: new FormData(form),
        })
            .then(async (response) => { if (! response.ok) throw await response.json(); loadPanel(panelUrl, 'form'); })
            .catch((error) => window.alert(error?.message ?? Object.values(error?.errors ?? {}).flat()[0] ?? 'No se pudo actualizar los archivos.'))
            .finally(() => { if (button) button.disabled = false; });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-presentation-dialog]');
        if (trigger) loadPanel(trigger.dataset.panelUrl);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-presentation-modal]')) dialog.close();
        if (event.target.closest('[data-show-generate-form]')) { showView('form'); syncPresentationForm(body); }
        if (event.target.closest('[data-show-list-view]')) showView('list');
        const faqAdd = event.target.closest('[data-faq-add]');
        if (faqAdd) {
            const list = body.querySelector('[data-faq-list]');
            list.insertAdjacentHTML('beforeend', body.querySelector('[data-faq-template]').innerHTML.replaceAll('__INDEX__', list.children.length));
            list.lastElementChild.querySelector('input').focus();
            syncFaqIndexes(list);
        }
        const faqRemove = event.target.closest('[data-faq-remove]');
        if (faqRemove) {
            const list = body.querySelector('[data-faq-list]');
            faqRemove.closest('.youtube-editor-row').remove();
            syncFaqIndexes(list);
        }
        const capture = event.target.closest('[data-croquis-capture]');
        if (capture) captureCroquisMap(capture.closest('[data-presentation-form]'));
        const clearCapture = event.target.closest('[data-croquis-clear]');
        if (clearCapture) clearCroquisCapture(clearCapture.closest('[data-presentation-form]'));
    });

    dialog.addEventListener('change', (event) => {
        if (event.target.matches('[data-presentation-document-input]') && event.target.files.length) {
            updateDocuments(event.target.form);
            return;
        }
        handlePresentationFormChange(body, event);
    });

    dialog.addEventListener('submit', (event) => {
        const documentDelete = event.target.closest('[data-presentation-document-delete]');
        if (documentDelete) {
            event.preventDefault();
            if (window.confirm('¿Eliminar este documento?')) updateDocuments(documentDelete);
            return;
        }
        const form = event.target.closest('[data-presentation-form]');
        if (! form) return;
        event.preventDefault();
        const submitButton = form.querySelector('[data-presentation-submit]');
        const formError = form.querySelector('[data-presentation-form-error]');
        submitButton.disabled = true;
        formError.hidden = true;
        fetch(form.action, {
            method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: new FormData(form),
        })
            .then(async (response) => {
                const data = await response.json();
                if (! response.ok) throw data;
                const list = body.querySelector('[data-presentation-list]');
                const row = body.querySelector('[data-presentation-row-template]').content.firstElementChild.cloneNode(true);
                row.dataset.presentationId = data.id;
                row.querySelector('strong').textContent = form.querySelector('input[name=template_key]:checked')
                    ?.closest('.template-option').querySelector('strong').textContent ?? 'Presentación';
                body.querySelector('[data-presentation-empty]')?.setAttribute('hidden', '');
                list.prepend(row);
                pollPresentation(body, list, row, data.id, statusLabels);
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
