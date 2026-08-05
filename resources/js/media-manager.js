document.querySelectorAll('.property-form').forEach((form) => {
    const input = form.querySelector('[data-media-input]');
    const list = form.querySelector('[data-media-list]');
    const manifest = form.querySelector('[data-media-manifest]');
    const drop = form.querySelector('[data-media-drop]');
    let dragged;
    if (! input || ! list) return;

    const notify = () => form.dispatchEvent(new CustomEvent('mediachanged', { bubbles: true }));
    const sync = (changed = true) => {
        const transfer = new DataTransfer();
        const tokens = [];
        let newIndex = 0;
        list.querySelectorAll('[data-media-card]').forEach((card) => {
            if (card._mediaFile) {
                transfer.items.add(card._mediaFile);
                card.dataset.mediaToken = `new:${newIndex++}`;
            }
            tokens.push(card.dataset.mediaToken);
        });
        input.files = transfer.files;
        manifest.value = JSON.stringify(tokens);
        if (changed) notify();
    };
    const preview = (file) => {
        const card = document.createElement('article');
        const url = URL.createObjectURL(file);
        const isImage = file.type.startsWith('image/');
        card.draggable = true;
        card.dataset.mediaCard = '';
        card._mediaFile = file;
        card._mediaUrl = url;
        card.innerHTML = `
            <button class="drag-handle" type="button" aria-label="Arrastrar para ordenar">
                <span class="material-symbols-rounded">drag_indicator</span>
            </button>
            ${isImage
                ? `<img src="${url}" alt="">`
                : `<video src="${url}" muted preload="metadata" playsinline></video>`}
            <strong></strong>
            <button class="remove-new-media" type="button" data-new-media-remove>Quitar</button>`;
        card.querySelector('strong').textContent = file.name;
        list.append(card);
    };
    const addFiles = (files) => {
        [...files].filter((file) => file.type.startsWith('image/')
            || file.type.startsWith('video/')).forEach(preview);
        sync();
    };

    input.addEventListener('change', () => addFiles(input.files));
    ['dragenter', 'dragover'].forEach((name) => drop.addEventListener(name, (event) => {
        event.preventDefault();
        drop.classList.add('drag-active');
    }));
    ['dragleave', 'drop'].forEach((name) => drop.addEventListener(name, (event) => {
        event.preventDefault();
        drop.classList.remove('drag-active');
    }));
    drop.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-new-media-remove]');
        if (! button) return;
        const card = button.closest('[data-media-card]');
        URL.revokeObjectURL(card._mediaUrl);
        card.remove();
        sync();
    });
    list.addEventListener('dragstart', (event) => {
        dragged = event.target.closest('[data-media-card]');
        dragged?.classList.add('dragging');
    });
    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        const target = event.target.closest('[data-media-card]');
        if (! dragged || ! target || dragged === target) return;
        const after = event.clientY > target.getBoundingClientRect().top
            + target.getBoundingClientRect().height / 2;
        list.insertBefore(dragged, after ? target.nextSibling : target);
    });
    list.addEventListener('dragend', () => {
        dragged?.classList.remove('dragging');
        dragged = null;
        sync();
    });
    sync(false);

    const coverInput = form.querySelector('[data-cover-input]');
    const coverDrop = form.querySelector('[data-cover-drop]');
    const coverPreview = form.querySelector('[data-cover-preview]');
    const coverPlaceholder = form.querySelector('[data-cover-placeholder]');
    const showCover = () => {
        const file = coverInput.files[0];
        if (! file) return;
        coverPreview.src = URL.createObjectURL(file);
        coverPreview.hidden = false;
        coverPlaceholder.hidden = true;
        notify();
    };
    coverInput?.addEventListener('change', showCover);
    coverDrop?.addEventListener('dragover', (event) => event.preventDefault());
    coverDrop?.addEventListener('drop', (event) => {
        event.preventDefault();
        const file = [...event.dataTransfer.files].find((item) => item.type.startsWith('image/'));
        if (! file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        coverInput.files = transfer.files;
        showCover();
    });
});
