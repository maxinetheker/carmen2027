import { collectFields, renderFields } from './property-import-fields';

const dialog = document.querySelector('[data-import-dialog]');

if (dialog) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const steps = {
        url: dialog.querySelector('[data-import-step="url"]'),
        review: dialog.querySelector('[data-import-step="review"]'),
    };
    const fieldsBox = dialog.querySelector('[data-import-fields]');
    const gallery = dialog.querySelector('[data-import-gallery]');
    const readButton = dialog.querySelector('[data-import-read]');
    const saveButton = dialog.querySelector('[data-import-save]');
    let parsed = null;

    const showStep = (name) => Object.entries(steps)
        .forEach(([key, step]) => { step.hidden = key !== name; });

    const showError = (selector, message) => {
        const box = dialog.querySelector(selector);
        box.textContent = message || '';
        box.hidden = ! message;
    };

    const post = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        const body = await response.json().catch(() => ({}));
        if (! response.ok) throw new Error(body.message || 'No se pudo completar la operación.');
        return body;
    };

    const renderGallery = (images) => {
        dialog.querySelector('[data-import-photo-count]').textContent = images.length;
        gallery.innerHTML = images.map((url, index) => `
            <label class="import-photo">
                <input type="checkbox" value="${index}" data-import-photo checked>
                <img src="${url}" alt="Foto ${index + 1}" loading="lazy">
                <span>${index === 0 ? 'Portada' : index + 1}</span>
            </label>`).join('')
            || '<p class="import-empty">La página no tenía fotos que se puedan descargar.</p>';
    };

    const showDuplicate = (duplicate) => {
        const box = dialog.querySelector('[data-import-duplicate]');
        box.hidden = ! duplicate;
        if (duplicate) {
            box.innerHTML = `Ya existe una propiedad parecida: <a href="${duplicate.url}">`
                + `${duplicate.code} · ${duplicate.title}</a>. Si continúas se creará una nueva.`;
        }
    };

    dialog.querySelectorAll('[data-import-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            dialog.querySelectorAll('[data-import-tab]').forEach((other) => other.classList.remove('active'));
            tab.classList.add('active');
            dialog.querySelectorAll('[data-import-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.importPanel !== tab.dataset.importTab;
            });
        });
    });

    readButton?.addEventListener('click', async () => {
        showError('[data-import-error]', '');
        readButton.disabled = true;
        readButton.textContent = 'Leyendo…';
        try {
            const body = await post(readButton.dataset.previewUrl, {
                url: dialog.querySelector('[data-import-url]').value.trim() || null,
                html: dialog.querySelector('[data-import-html]').value.trim() || null,
            });
            parsed = body.data;
            renderFields(fieldsBox, parsed);
            renderGallery(parsed.images || []);
            showDuplicate(parsed.duplicate);
            showStep('review');
        } catch (error) {
            showError('[data-import-error]', error.message);
        } finally {
            readButton.disabled = false;
            readButton.textContent = 'Leer la propiedad';
        }
    });

    dialog.querySelector('[data-import-toggle-all]')?.addEventListener('change', (event) => {
        gallery.querySelectorAll('[data-import-photo]')
            .forEach((box) => { box.checked = event.target.checked; });
    });

    dialog.querySelector('[data-import-back]')?.addEventListener('click', () => showStep('url'));

    saveButton?.addEventListener('click', async () => {
        showError('[data-import-error-review]', '');
        const { values, missing } = collectFields(fieldsBox);
        if (missing.length) {
            showError('[data-import-error-review]', `Completa: ${missing.join(', ')}.`);
            return;
        }
        const images = [...gallery.querySelectorAll('[data-import-photo]:checked')]
            .map((box) => parsed.images[Number(box.value)]);
        saveButton.disabled = true;
        saveButton.textContent = 'Guardando y descargando fotos…';
        try {
            const body = await post(saveButton.dataset.storeUrl, {
                ...values,
                features: parsed.features || [],
                images,
                source_url: parsed.source_url,
            });
            window.location.href = body.redirect;
        } catch (error) {
            showError('[data-import-error-review]', error.message);
            saveButton.disabled = false;
            saveButton.textContent = 'Guardar propiedad';
        }
    });

    document.querySelectorAll('[data-open-import]').forEach((button) => {
        button.addEventListener('click', () => {
            showStep('url');
            showError('[data-import-error]', '');
            dialog.showModal();
        });
    });
}
