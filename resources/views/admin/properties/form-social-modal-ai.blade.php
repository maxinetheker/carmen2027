{{-- The AI toggles this modal keeps: what to say and what to draw. Deliberately absent
     compared with the brochure form: plantilla, límite de hojas e instrucciones extra. --}}
<fieldset class="modal-fieldset">
    <legend>Información actual para generar interés</legend>
    <select name="interest_mode" data-mode-select data-reveals="interest_manual">
        <option value="auto" selected>Automático (la IA investiga)</option>
        <option value="manual">Manual (lo escribo yo)</option>
        <option value="off">Desactivado</option>
    </select>
    <textarea name="interest_manual" rows="3" placeholder="Motivos para comprar esta propiedad ahora…"
        data-reveal-target="interest_manual" hidden></textarea>
    <p class="field-hint">De aquí salen los puntos fuertes que aparecen en la pieza.</p>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Título llamativo</legend>
    <select name="title_mode" data-mode-select data-reveals="title_manual">
        <option value="auto" selected>Automático (la IA lo redacta)</option>
        <option value="manual">Manual</option>
        <option value="off">Desactivado (usa el título de la propiedad)</option>
    </select>
    <input type="text" name="title_manual" maxlength="160" placeholder="Titular para la pieza…"
        data-reveal-target="title_manual" hidden>
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Croquis de ubicación</legend>
    <select name="croquis_mode">
        <option value="auto" @selected($hasLocation)>Automático (mapa de la ubicación guardada)</option>
        <option value="off" @selected(! $hasLocation)>Desactivado</option>
    </select>
    @if($hasLocation)
        <p class="field-hint">Se inserta como recuadro pequeño, generado desde las coordenadas de la propiedad.</p>
    @else
        <p class="modal-error">Esta propiedad no tiene ubicación marcada, así que el croquis no está disponible.</p>
    @endif
</fieldset>

<fieldset class="modal-fieldset">
    <legend>Dirigido a</legend>
    <select name="audience">
        <option value="personas" selected>Personas / familias</option>
        <option value="empresas">Empresas / inversionistas</option>
    </select>
</fieldset>
