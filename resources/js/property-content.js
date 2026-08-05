document.querySelectorAll('[data-rich-wrap]').forEach((wrapper) => {
    const editor = wrapper.querySelector('[data-rich-editor]');
    const input = wrapper.querySelector('[data-rich-input]');
    const picker = wrapper.querySelector('[data-emoji-picker]');
    let savedRange;
    const remember = () => {
        const selection = window.getSelection();
        if (selection?.rangeCount && editor.contains(selection.anchorNode)) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }
    };
    const normalizePastedImages = () => editor.querySelectorAll('img').forEach((image) => {
        image.replaceWith(document.createTextNode(image.getAttribute('alt') || ''));
    });
    const sync = () => {
        input.value = editor.innerHTML;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };
    wrapper.querySelectorAll('[data-rich-command]').forEach((button) => {
        button.addEventListener('click', () => {
            editor.focus();
            document.execCommand(
                button.dataset.richCommand, false, button.dataset.richValue || null
            );
            sync();
        });
    });
    wrapper.querySelector('[data-rich-link]')?.addEventListener('click', () => {
        const url = window.prompt('Dirección del enlace (https://...)');
        if (! url) return;
        editor.focus();
        document.execCommand('createLink', false, url);
        sync();
    });
    wrapper.querySelector('[data-emoji-toggle]')?.addEventListener('click', (event) => {
        picker.hidden = ! picker.hidden;
        event.currentTarget.setAttribute('aria-expanded', String(! picker.hidden));
    });
    picker?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-emoji]');
        if (! button) return;
        editor.focus();
        if (savedRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(savedRange);
        }
        document.execCommand('insertText', false, button.dataset.emoji);
        picker.hidden = true;
        sync();
    });
    editor.addEventListener('input', sync);
    editor.addEventListener('paste', () => window.setTimeout(() => {
        normalizePastedImages();
        sync();
    }));
    editor.addEventListener('keyup', remember);
    editor.addEventListener('mouseup', remember);
    editor.closest('form')?.addEventListener('submit', sync);
});

document.querySelectorAll('.property-form').forEach((form) => {
    const list = form.querySelector('[data-feature-list]');
    const template = form.querySelector('[data-feature-template]');
    const reindex = () => list?.querySelectorAll('.feature-editor-row')
        .forEach((row, index) => row.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace(
                /features\[(?:\d+|__INDEX__)\]/, `features[${index}]`
            );
        }));
    const addFeature = (icon = 'info', label = '') => {
        list.insertAdjacentHTML(
            'beforeend', template.innerHTML.replaceAll('__INDEX__', list.children.length)
        );
        const row = list.lastElementChild;
        row.querySelector('select').value = icon;
        row.querySelector('[data-feature-icon]').textContent = icon;
        row.querySelector('[name$="[label]"]').value = label;
        row.querySelector('[name$="[value]"]').focus();
        reindex();
        form.dispatchEvent(new Event('input', { bubbles: true }));
    };
    form.querySelector('[data-feature-add]')?.addEventListener('click', () => addFeature());
    form.querySelectorAll('[data-feature-preset]').forEach((button) => {
        button.addEventListener('click', () => addFeature(button.dataset.icon, button.dataset.label));
    });
    list?.addEventListener('change', (event) => {
        if (event.target.tagName !== 'SELECT') return;
        event.target.closest('.feature-editor-row')
            .querySelector('[data-feature-icon]').textContent = event.target.value;
    });
    list?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-feature-remove]');
        if (! button) return;
        button.closest('.feature-editor-row').remove();
        reindex();
        form.dispatchEvent(new Event('input', { bubbles: true }));
    });
});

document.querySelectorAll('[data-property-gallery]').forEach((gallery) => {
    const panels = [...gallery.querySelectorAll('[data-gallery-panel]')];
    const buttons = [...gallery.querySelectorAll('[data-gallery-target]')];
    buttons.forEach((button) => button.addEventListener('click', () => {
        const target = Number(button.dataset.galleryTarget);
        panels.forEach((panel, index) => {
            panel.hidden = index !== target;
            if (index !== target) {
                panel.querySelector('video')?.pause();
                const frame = panel.querySelector('iframe');
                if (frame) frame.src = frame.src;
            }
        });
        buttons.forEach((item, index) => {
            item.classList.toggle('active', index === target);
            item.setAttribute('aria-selected', String(index === target));
        });
    }));
});
