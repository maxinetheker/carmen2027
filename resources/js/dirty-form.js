document.querySelectorAll('[data-dirty-form]').forEach((form) => {
    const bar = form.querySelector('[data-save-bar]');
    const button = form.querySelector('[data-save-button]');
    const undo = form.querySelector('[data-undo-changes]');
    if (! bar || ! button) return;
    let initialState = '';
    let ready = false;
    let dirty = false;
    const snapshot = () => [...new FormData(form).entries()]
        .filter(([name]) => ! ['_token', '_method'].includes(name))
        .map(([name, value]) => [name, value instanceof File
            ? `${value.name}:${value.size}:${value.lastModified}` : String(value)]);
    const sync = () => {
        if (! ready) return;
        dirty = JSON.stringify(snapshot()) !== initialState;
        bar.hidden = ! dirty;
    };
    const initialize = () => {
        initialState = JSON.stringify(snapshot());
        ready = true;
        dirty = false;
        bar.hidden = true;
    };
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    form.addEventListener('mediachanged', sync);
    form.addEventListener('formbaselinechange', () => {
        if (! dirty) initialize();
    });
    form.addEventListener('submit', () => {
        dirty = false;
        button.disabled = true;
        button.textContent = 'Guardando…';
    });
    undo?.addEventListener('click', () => {
        if (! window.confirm('¿Deshacer todos los cambios y recuperar la última versión guardada?')) return;
        dirty = false;
        window.location.reload();
    });
    window.addEventListener('beforeunload', (event) => {
        if (! dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
    window.setTimeout(initialize);
});
