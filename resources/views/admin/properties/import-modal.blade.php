<dialog class="import-modal" data-import-dialog>
    <form method="dialog" class="import-close"><button aria-label="Cerrar">×</button></form>
    <div class="import-step" data-import-step="url">
        <h2>Importar desde un enlace</h2>
        <p>Pega la dirección de la ficha en remax.pe y se cargarán los datos y las fotos para que revises qué guardar.</p>
        <div class="import-tabs" role="tablist">
            <button type="button" class="active" data-import-tab="link" role="tab">Enlace de la propiedad</button>
            <button type="button" data-import-tab="html" role="tab">Pegar el código de la página</button>
        </div>
        <div data-import-panel="link">
            <label class="field">
                <span>URL de la propiedad</span>
                <input type="url" data-import-url
                    placeholder="https://www.remax.pe/web/search/property/propiedad-casa-en-venta-...">
            </label>
        </div>
        <div data-import-panel="html" hidden>
            <label class="field">
                <span>Código fuente de la página</span>
                <textarea rows="6" data-import-html
                    placeholder="Abre la propiedad en tu navegador, presiona Ctrl+U, copia todo y pégalo aquí."></textarea>
                <small class="field-help">Úsalo solo si el portal bloquea la descarga automática.</small>
            </label>
        </div>
        <p class="import-error" data-import-error hidden></p>
        <div class="import-actions">
            <button class="button button-accent" type="button" data-import-read
                data-preview-url="{{ route('admin.properties.import.preview') }}">Leer la propiedad</button>
        </div>
    </div>

    <div class="import-step" data-import-step="review" hidden>
        <h2>¿Qué quieres cargar?</h2>
        <p class="import-duplicate" data-import-duplicate hidden></p>
        <div class="import-grid" data-import-fields></div>
        <div class="import-gallery-heading">
            <strong>Fotos encontradas (<span data-import-photo-count>0</span>)</strong>
            <label class="checkbox-inline"><input type="checkbox" data-import-toggle-all checked><span>Seleccionar todas</span></label>
        </div>
        <div class="import-gallery" data-import-gallery></div>
        <p class="import-error" data-import-error-review hidden></p>
        <div class="import-actions">
            <button class="button button-ghost-dark" type="button" data-import-back>Volver</button>
            <button class="button button-accent" type="button" data-import-save
                data-store-url="{{ route('admin.properties.import.store') }}">Guardar propiedad</button>
        </div>
    </div>
</dialog>
