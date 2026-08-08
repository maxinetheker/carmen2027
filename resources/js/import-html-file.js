const maxHtmlSize = 6_000_000;

const isHtmlFile = (file) => /\.(?:html?|xhtml)$/i.test(file.name)
    || ['text/html', 'application/xhtml+xml'].includes(file.type);

export const bindHtmlFileInput = (fileInput, htmlInput, fileName, showError) => {
    if (! fileInput || ! htmlInput || ! fileName) return;

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        showError('');
        fileName.hidden = true;
        if (! file) return;
        if (! isHtmlFile(file)) {
            showError('Selecciona un archivo HTML (.html o .htm).');
            fileInput.value = '';
            return;
        }
        if (file.size > maxHtmlSize) {
            showError('El archivo HTML no puede superar los 6 MB.');
            fileInput.value = '';
            return;
        }

        try {
            const source = (await file.text()).trim();
            if (! source) throw new Error('El archivo HTML está vacío.');
            htmlInput.value = source;
            fileName.textContent = `Archivo cargado: ${file.name}`;
            fileName.hidden = false;
        } catch (error) {
            showError(error.message || 'No se pudo leer el archivo HTML.');
            fileInput.value = '';
        }
    });
};
