@php
    $images = $record->media->where('type', 'image')->values();
    $hasLocation = (bool) ($record->latitude && $record->longitude);
    $templates = config('brochure_templates.templates');
    $logos = config('brochure_templates.logos');
    $imagesCfg = config('brochure_templates.max_images');
    $pagesCfg = config('brochure_templates.max_pages');
    $renderer = app(\App\Services\Brochure\PresentationRenderer::class);
@endphp

<form data-presentation-form action="{{ route('admin.properties.presentations.store', $record) }}" enctype="multipart/form-data">
    <h3>Generar presentación PDF</h3>

    <fieldset class="modal-fieldset">
        <legend>Plantilla</legend>
        <div class="template-picker">
            @foreach($templates as $key => $theme)
                <label class="template-option">
                    <input type="radio" name="template_key" value="{{ $key }}" @checked($loop->first) required>
                    <span class="template-preview-frame">
                        <iframe srcdoc="{{ $renderer->previewCoverHtml($key) }}" tabindex="-1" title="Vista previa {{ $theme['label'] }}"></iframe>
                    </span>
                    <strong>{{ $theme['label'] }}</strong>
                </label>
            @endforeach
        </div>
    </fieldset>

    @include('admin.properties.form-presentation-modal-logo')

    <fieldset class="modal-fieldset">
        <legend>Imágenes</legend>
        <div class="mode-toggle">
            <label><input type="radio" name="images_mode" value="auto" data-images-mode checked> Automático (la IA elige)</label>
            <label><input type="radio" name="images_mode" value="manual" data-images-mode> Manual (yo elijo)</label>
        </div>
        <label class="field field-inline">
            <span>Cantidad de imágenes a mostrar</span>
            <input type="number" name="image_count" min="{{ $imagesCfg['min'] }}" max="{{ $imagesCfg['max'] }}" value="{{ $imagesCfg['default'] }}">
        </label>
        <div class="image-picker" data-manual-images hidden>
            @forelse($images as $media)
                <label class="image-option">
                    <input type="checkbox" name="selected_image_ids[]" value="{{ $media->id }}">
                    <img src="{{ $media->url }}" alt="">
                    <span class="image-option-cover">
                        <input type="radio" name="cover_media_id" value="{{ $media->id }}" @checked($media->is_cover)> Portada
                    </span>
                </label>
            @empty
                <p class="document-empty">Esta propiedad no tiene fotos todavía.</p>
            @endforelse
        </div>
    </fieldset>

    @include('admin.properties.form-presentation-modal-ai-fields')

    <div class="modal-actions">
        <button class="button button-ghost-dark" type="button" data-show-list-view>Cancelar</button>
        <button class="button button-accent" type="submit" data-presentation-submit>Generar</button>
    </div>
    <p class="modal-error" data-presentation-form-error hidden></p>
</form>
