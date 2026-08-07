import { handlePresentationFormChange, syncImageControls, syncReveals } from './presentation-form-controls';
import { pollSocialImage } from './social-polling';

const dialog = document.querySelector('[data-social-dialog]');
if (dialog) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const body = dialog.querySelector('[data-social-body]');
    let panelUrl = null;

    const showView = (name) => body.querySelectorAll('[data-panel-view]')
        .forEach((view) => { view.hidden = view.dataset.panelView !== name; });

    // The logo picker and the agent pose only make sense once their parent option is on.
    const syncForm = () => {
        const form = body.querySelector('[data-social-form]');
        if (! form) return;
        form.querySelectorAll('[data-mode-select]').forEach((select) => syncReveals(body, select));
        syncImageControls(form);
        const logoKey = form.querySelector('[data-social-logo-key]');
        if (logoKey) logoKey.hidden = form.querySelector('[data-social-logo-mode]')?.value !== 'manual';
        const pose = form.querySelector('[data-agent-pose]');
        if (pose) pose.hidden = ! form.querySelector('[data-agent-toggle]')?.checked;
    };

    const loadPanel = (url, view = 'list') => {
        panelUrl = url;
        body.innerHTML = '<p class="document-empty">Cargando…</p>';
        dialog.showModal();
        fetch(url, { headers: { Accept: 'text/html' } })
            .then((response) => response.text())
            .then((html) => {
                body.innerHTML = html;
                syncForm();
                showView(view);
                body.querySelectorAll('[data-social-list] [data-social-row]:not([data-poll-done])').forEach((row) => {
                    if (row.dataset.socialId) pollSocialImage(body, row, row.dataset.socialId);
                });
            })
            .catch(() => { body.innerHTML = '<p class="document-empty">No se pudo cargar. Intenta de nuevo.</p>'; });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-social-dialog]');
        if (trigger) loadPanel(trigger.dataset.panelUrl);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-social-modal]')) dialog.close();
        if (event.target.closest('[data-show-social-form]')) { showView('form'); syncForm(); }
        if (event.target.closest('[data-show-social-list]')) showView('list');
    });

    dialog.addEventListener('change', (event) => {
        handlePresentationFormChange(body, event);
        syncForm();
    });

    dialog.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-social-form]');
        if (! form) return;
        event.preventDefault();
        const submitButton = form.querySelector('[data-social-submit]');
        const formError = form.querySelector('[data-social-form-error]');
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
                const list = body.querySelector('[data-social-list]');
                const row = body.querySelector('[data-social-row-template]').content.firstElementChild.cloneNode(true);
                row.dataset.socialId = data.id;
                row.querySelector('strong').textContent = form.querySelector('input[name=format]:checked')
                    ?.closest('.format-option').querySelector('strong').textContent ?? 'Imagen';
                body.querySelector('[data-social-empty]')?.setAttribute('hidden', '');
                list.prepend(row);
                pollSocialImage(body, row, data.id);
                showView('list');
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
