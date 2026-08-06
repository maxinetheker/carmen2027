<fieldset class="modal-fieldset">
    <legend>Información actual para generar interés</legend>
    <p class="field-hint">La IA busca en internet argumentos reales y puede usar HTML simple y seguro para resaltar el contenido.</p>
    <select name="interest_mode" data-mode-select data-reveals="interest_manual">
        <option value="auto">Automático (la IA investiga)</option>
        <option value="manual">Manual (lo escribo yo)</option>
        <option value="off">Desactivado</option>
    </select>
    <textarea name="interest_manual" rows="3" placeholder="Motivos para comprar esta propiedad ahora…" data-reveal-target="interest_manual" hidden></textarea>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Preguntas frecuentes</legend>
    <select name="faq_mode" data-mode-select data-reveals="faq_manual">
        <option value="auto">Automático (generadas por IA)</option>
        <option value="manual">Manual</option>
        <option value="off" selected>Desactivado</option>
    </select>
    <div class="youtube-editor-list" data-faq-list data-reveal-target="faq_manual" hidden></div>
    <button class="mini-button" type="button" data-faq-add hidden data-reveal-target="faq_manual">+ Agregar pregunta</button>
    <template data-faq-template>
        <div class="youtube-editor-row">
            <label class="field"><span>Pregunta</span><input type="text" name="faq_manual[__INDEX__][question]" maxlength="200"></label>
            <label class="field"><span>Respuesta</span><input type="text" name="faq_manual[__INDEX__][answer]" maxlength="1000"></label>
            <button class="feature-remove" type="button" data-faq-remove aria-label="Quitar pregunta">×</button>
        </div>
    </template>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Título llamativo</legend>
    <select name="title_mode" data-mode-select data-reveals="title_manual">
        <option value="auto">Automático (la IA lo redacta)</option>
        <option value="manual">Manual</option>
        <option value="off" selected>Desactivado (usa el título de la propiedad)</option>
    </select>
    <input type="text" name="title_manual" maxlength="160" placeholder="Título para la portada…" data-reveal-target="title_manual" hidden>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Croquis de ubicación</legend>
    <select name="croquis_mode" data-croquis-mode>
        <option value="auto" selected>Automático (la IA genera el croquis)</option>
        <option value="off">Desactivado</option>
    </select>
    <label class="field" data-croquis-reference>
        <span>Captura de mapa opcional</span>
        <input type="file" name="croquis_reference" accept="image/jpeg,image/png,image/webp">
    </label>
    <p class="field-hint">Puedes subir una captura; la IA la combinará con el GPS registrado cuando esté disponible y generará un croquis HTML seguro.</p>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Dirigido a</legend>
    <select name="audience">
        <option value="personas" selected>Personas / familias</option>
        <option value="empresas">Empresas / inversionistas</option>
    </select>
    <p class="field-hint">Cambia el enfoque del contenido generado (hogar vs. retorno de inversión).</p>
</fieldset>

<fieldset class="modal-fieldset">
    <label class="field">
        <span>Límite máximo de hojas</span>
        <input type="number" name="max_pages" min="{{ $pagesCfg['min'] }}" max="{{ $pagesCfg['max'] }}" value="{{ $pagesCfg['default'] }}">
    </label>
    <p class="field-hint">La IA decide cuántas hojas usar, sin superar este límite y sin crear hojas vacías.</p>
    <label class="field">
        <span>Instrucciones adicionales para la IA (opcional)</span>
        <textarea name="extra_prompt" rows="2" placeholder="Ej: enfatizar que acepta financiamiento bancario…"></textarea>
    </label>
</fieldset>
