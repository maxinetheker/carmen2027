document.querySelectorAll('[data-seo-drop]').forEach((drop) => {
    const input = drop.querySelector('[data-seo-input]');
    const preview = drop.querySelector('[data-seo-preview]');
    if (! input || ! preview) return;

    const show = () => {
        const file = input.files[0];
        if (! file?.type.startsWith('image/')) return;
        preview.src = URL.createObjectURL(file);
    };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach((name) => drop.addEventListener(name, (event) => {
        event.preventDefault();
        drop.classList.add('drag-active');
    }));
    ['dragleave', 'drop'].forEach((name) => drop.addEventListener(name, (event) => {
        event.preventDefault();
        drop.classList.remove('drag-active');
    }));
    drop.addEventListener('drop', (event) => {
        const file = [...event.dataTransfer.files]
            .find((item) => item.type.startsWith('image/'));
        if (! file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        show();
    });
});
