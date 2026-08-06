const targetControls = (target) => [target, ...target.querySelectorAll?.('input, select, textarea, button') ?? []]
    .filter((element, index, all) => all.indexOf(element) === index)
    .filter((element) => element.matches?.('input, select, textarea, button'));

const setTargetState = (target, hidden) => {
    target.hidden = hidden;
    targetControls(target).forEach((control) => { control.disabled = hidden; });
};

export const syncReveals = (body, select) => {
    const key = select.dataset.reveals;
    body.querySelectorAll(`[data-reveal-target="${key}"]`).forEach((target) => {
        setTargetState(target, select.value !== 'manual');
    });
};

export const syncImageControls = (form) => {
    const isManual = form.querySelector('[data-images-mode]:checked')?.value === 'manual';
    const picker = form.querySelector('[data-manual-images]');
    if (! picker) return;

    setTargetState(picker, ! isManual);
    if (! isManual) return;
    picker.querySelectorAll('[data-cover-image]').forEach((radio) => {
        const checkbox = radio.closest('.image-option')?.querySelector('[data-manual-image]');
        radio.disabled = ! checkbox?.checked;
        if (radio.disabled && radio.checked) radio.checked = false;
    });
    const selected = [...picker.querySelectorAll('[data-manual-image]:checked')];
    const countLabel = picker.querySelector('[data-manual-image-count]');
    if (countLabel) {
        countLabel.textContent = `${selected.length} imagen${selected.length === 1 ? '' : 'es'} seleccionada${selected.length === 1 ? '' : 's'}; elige una como principal.`;
    }
    if (! picker.querySelector('[data-cover-image]:checked') && selected[0]) {
        const cover = selected[0].closest('.image-option')?.querySelector('[data-cover-image]');
        if (cover) cover.checked = true;
    }
};

export const syncCroquis = (form) => {
    const mode = form.querySelector('[data-croquis-mode]');
    const reference = form.querySelector('[data-croquis-reference]');
    if (mode && reference) setTargetState(reference, mode.value !== 'auto');
};

export const syncPresentationForm = (body) => {
    const form = body.querySelector('[data-presentation-form]');
    if (! form) return;
    form.querySelectorAll('[data-mode-select]').forEach((select) => syncReveals(body, select));
    syncImageControls(form);
    syncCroquis(form);
};

export const handlePresentationFormChange = (body, event) => {
    const form = event.target.closest('[data-presentation-form]');
    if (! form) return;
    if (event.target.matches('[data-images-mode]')) syncImageControls(form);
    if (event.target.matches('[data-mode-select]')) syncReveals(body, event.target);
    if (event.target.matches('[data-croquis-mode]')) syncCroquis(form);
    if (event.target.matches('[data-manual-image]')) {
        const radio = event.target.closest('.image-option')?.querySelector('[data-cover-image]');
        if (! event.target.checked && radio?.checked) radio.checked = false;
        syncImageControls(form);
    }
    if (event.target.matches('[data-cover-image]')) {
        const checkbox = event.target.closest('.image-option')?.querySelector('[data-manual-image]');
        if (checkbox) checkbox.checked = true;
        syncImageControls(form);
    }
};
