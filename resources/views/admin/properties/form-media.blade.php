@php
    $hasCover = $record->exists && ($record->media->contains('type', 'image') || $record->image_url);
    $manifest = $record->exists
        ? $record->media->map(fn ($media) => 'existing:'.$media->id)->values()
        : collect();
@endphp

<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Imagen principal</h2><p>Se previsualiza al instante y se comprime automáticamente al guardar.</p></div>
    </div>
    <label class="cover-uploader" data-cover-drop>
        <img src="{{ $hasCover ? $record->cover_url : '' }}" alt="" data-cover-preview @if(!$hasCover) hidden @endif>
        <span class="cover-placeholder" data-cover-placeholder @if($hasCover) hidden @endif>
            <i class="material-symbols-rounded">add_photo_alternate</i>
            <strong>Arrastra o elige la imagen principal</strong>
            <small>JPG, PNG, WebP o AVIF · máximo 15 MB</small>
        </span>
        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/avif" data-cover-input>
    </label>
    <details class="legacy-url">
        <summary>Usar una imagen alojada en otra web</summary>
        <label class="field"><span>Dirección web de imagen externa</span>
            <input type="text" inputmode="url" name="image_url"
                value="{{ old('image_url', $record->image_url) }}"
                placeholder="https://... o /images/propiedad.jpg">
        </label>
    </details>
</section>

<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Galería multimedia</h2><p>Arrastra las tarjetas para definir el orden de fotos y videos.</p></div>
    </div>
    <label class="upload-drop media-drop" data-media-drop>
        <span class="material-symbols-rounded">perm_media</span>
        <strong>Agregar fotos o videos</strong>
        <small>Sin límite de cantidad · Fotos hasta 15 MB · MP4, WebM o MOV hasta 200 MB</small>
        <input type="file" name="media_files[]" multiple data-media-input
            accept="image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm,video/quicktime">
    </label>
    <p class="media-notice" data-media-notice hidden></p>
    @unless($record->exists)
        <p class="field-hint">Guarda la propiedad para subir archivos sin límite de cantidad y ver el avance de cada uno.</p>
    @endunless
    <input type="hidden" name="media_manifest" value="{{ $manifest->toJson() }}" data-media-manifest>
    {{-- The endpoint is what switches the gallery to one-request-per-file uploads;
         it only exists once the property has an id. --}}
    <div class="admin-media-grid sortable-media" data-media-list
        @if($record->exists) data-media-endpoint="{{ route('admin.properties.media.store', $record) }}" @endif>
        @if($record->exists)
            @foreach($record->media as $media)
                <article draggable="true" data-media-card data-media-token="existing:{{ $media->id }}">
                    <button class="drag-handle" type="button" aria-label="Arrastrar para ordenar"><span class="material-symbols-rounded">drag_indicator</span></button>
                    @if($media->type === 'image')
                        <img src="{{ $media->url }}" alt="">
                    @else
                        <video src="{{ $media->url }}" muted preload="metadata" playsinline></video>
                    @endif
                    <strong>{{ Str::limit($media->original_name, 30) }}</strong>
                    @if($media->type === 'image')
                        <label><input type="radio" name="cover_media_id" value="{{ $media->id }}" @checked($media->is_cover)> Usar como principal</label>
                    @endif
                    <label class="remove-media"><input type="checkbox" name="remove_media[]" value="{{ $media->id }}"> Eliminar al guardar</label>
                </article>
            @endforeach
        @endif
    </div>
    <p class="media-help"><span class="material-symbols-rounded">speed</span> Las fotos se optimizan a WebP manteniendo su proporción. Los videos conservan sus dimensiones y cargan solo sus metadatos hasta reproducirse.</p>
</section>
