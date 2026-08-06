<fieldset class="modal-fieldset">
    <legend>Logo</legend>
    <select name="logo_mode" data-mode-select data-reveals="logo_manual">
        <option value="auto" selected>Automático (la IA decide)</option>
        <option value="manual">Manual (elijo uno)</option>
        <option value="off">Desactivado (sin logo)</option>
    </select>
    <p class="field-hint">En automático la IA escoge la variante con mejor contraste para la plantilla.</p>
    <div class="logo-picker" data-reveal-target="logo_manual" hidden>
        @foreach($logos as $key => $logo)
            <label class="logo-option">
                <input type="radio" name="logo_key" value="{{ $key }}" @checked($key === config('brochure_templates.default_logo'))>
                <img src="{{ route('admin.brochure.logo', $key) }}" alt="">
                <span>{{ $logo['label'] }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
