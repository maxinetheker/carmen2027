@php
    $images = $record->media->where('type', 'image')->values();
    $hasLocation = (bool) ($record->latitude && $record->longitude);
    $logos = config('brochure_templates.logos');
@endphp

{{-- Intentionally shorter than the brochure form: a post is a single picture, so there
     is no template, no page limit and no free-form prompt — only shape and quality. --}}
<form data-social-form action="{{ route('admin.properties.social.store', $record) }}" enctype="multipart/form-data">
    <h3>Generar imagen para redes sociales</h3>

    <fieldset class="modal-fieldset">
        <legend>Formato</legend>
        <div class="format-picker">
            @foreach([
                'cuadrado' => ['Cuadrada', '1:1 · feed de Instagram y Facebook'],
                'vertical' => ['Vertical', '2:3 · historias y reels'],
                'horizontal' => ['Horizontal', '3:2 · portada de Facebook y LinkedIn'],
            ] as $key => [$label, $hint])
                <label class="format-option format-{{ $key }}">
                    <input type="radio" name="format" value="{{ $key }}" @checked($loop->first) required>
                    <span class="format-shape"></span>
                    <strong>{{ $label }}</strong>
                    <small>{{ $hint }}</small>
                </label>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="modal-fieldset">
        <legend>Calidad</legend>
        <select name="quality">
            <option value="media" selected>Media (recomendada)</option>
            <option value="baja">Baja (más rápida y económica)</option>
        </select>
        <p class="field-hint">La calidad alta multiplica varias veces el tiempo y el costo, por eso no se ofrece aquí.</p>
    </fieldset>

    <fieldset class="modal-fieldset">
        <legend>Logotipo</legend>
        <select name="logo_mode" data-social-logo-mode>
            <option value="auto" selected>Automático (la IA elige)</option>
            <option value="manual">Manual</option>
            <option value="off">Sin logotipo</option>
        </select>
        <select name="logo_key" data-social-logo-key hidden>
            @foreach($logos as $key => $logo)
                <option value="{{ $key }}">{{ $logo['label'] ?? $key }}</option>
            @endforeach
        </select>
    </fieldset>

    <fieldset class="modal-fieldset">
        <legend>Imágenes</legend>
        <div class="mode-toggle">
            <label><input type="radio" name="images_mode" value="auto" data-images-mode checked> Automático (la IA elige)</label>
            <label><input type="radio" name="images_mode" value="manual" data-images-mode> Manual (yo elijo)</label>
        </div>
        <div class="image-picker" data-manual-images hidden>
            <p class="field-hint image-picker-count" data-manual-image-count>Selecciona las imágenes y una principal.</p>
            @forelse($images as $media)
                <label class="image-option">
                    <input type="checkbox" name="selected_image_ids[]" value="{{ $media->id }}" data-manual-image>
                    <img src="{{ $media->url }}" alt="">
                    <span class="image-option-cover">
                        <input type="radio" name="cover_media_id" value="{{ $media->id }}" data-cover-image @checked($media->is_cover)> Imagen principal
                    </span>
                </label>
            @empty
                <p class="document-empty">Esta propiedad no tiene fotos todavía.</p>
            @endforelse
        </div>
    </fieldset>

    <fieldset class="modal-fieldset">
        <legend>Foto de la asesora</legend>
        <label class="checkbox-row">
            <input type="checkbox" name="include_agent" value="1" data-agent-toggle>
            <span>Incluir a Carmen en la pieza</span>
        </label>
        <label class="field" data-agent-pose hidden>
            <span>Cómo debe aparecer</span>
            <input type="text" name="agent_pose" maxlength="200"
                value="de pie a un lado, de cuerpo entero, mirando a cámara y sonriendo">
        </label>
        <p class="field-hint">Se usa su foto real del sitio web; la IA no debe cambiarle el rostro ni la ropa.</p>
    </fieldset>

    @include('admin.properties.form-social-modal-ai')

    <div class="modal-actions">
        <button class="button button-ghost-dark" type="button" data-show-social-list>Cancelar</button>
        <button class="button button-accent" type="submit" data-social-submit>Generar imagen</button>
    </div>
    <p class="modal-error" data-social-form-error hidden></p>
</form>
