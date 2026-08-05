@extends('layouts.admin')

@section('title', 'Editar sitio')
@section('heading', 'Contenido del sitio web')
@section('eyebrow', 'Presencia digital')

@section('content')
<div class="settings-layout">
    <form class="resource-form" method="post" enctype="multipart/form-data"
        action="{{ route('admin.settings.update') }}">
        @csrf @method('put')
        <div class="form-card">
            <div class="form-card-heading">
                <div><h2>Textos y datos públicos</h2><p>Los cambios se reflejan en la página principal.</p></div>
                <span>Editable</span>
            </div>
            <div class="form-grid">
                @foreach($fields as $key => [$label, $type])
                    @continue(str_starts_with($key, 'seo_'))
                    <label class="field @if($type === 'textarea') field-wide @endif">
                        <span>{{ $label }}</span>
                        @if($type === 'textarea')
                            <textarea name="{{ $key }}" rows="4">{{ old($key, $settings[$key] ?? '') }}</textarea>
                        @else
                            <input type="{{ $type }}" name="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}">
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-card seo-settings-card">
            <div class="form-card-heading">
                <div><h2>SEO y redes sociales</h2><p>Define cómo se presenta la web en Google, WhatsApp y redes.</p></div>
                <span>Posicionamiento</span>
            </div>
            <div class="form-grid">
                @foreach($fields as $key => [$label, $type])
                    @continue(! str_starts_with($key, 'seo_'))
                    <label class="field field-wide">
                        <span>{{ $label }}</span>
                        @if($type === 'textarea')
                            <textarea name="{{ $key }}" rows="3" maxlength="160">{{ old($key, $settings[$key] ?? '') }}</textarea>
                        @else
                            <input type="{{ $type }}" name="{{ $key }}" maxlength="65"
                                value="{{ old($key, $settings[$key] ?? '') }}">
                        @endif
                    </label>
                @endforeach
            </div>
            <label class="seo-image-drop" data-seo-drop>
                <img src="{{ asset($settings['seo_image_path'] ?? 'og-blue-red.png') }}"
                    alt="Vista previa de la imagen SEO" data-seo-preview>
                <span>
                    <i class="material-symbols-rounded">add_photo_alternate</i>
                    <strong>Arrastra o elige la imagen para compartir</strong>
                    <small>Recomendado: 1200 × 630 px · JPG, PNG o WebP</small>
                </span>
                <input type="file" name="seo_image" accept="image/jpeg,image/png,image/webp" data-seo-input>
            </label>
        </div>

        <div class="form-actions">
            <a class="button button-ghost-dark" href="{{ route('home') }}" target="_blank" rel="noopener">Ver sitio ↗</a>
            <button class="button button-accent">Publicar cambios</button>
        </div>
    </form>

    <aside class="settings-preview">
        <div class="settings-preview-heading">
            <div><span class="eyebrow">Vista previa</span><strong>Portada publicada</strong></div>
            <small>Solo lectura</small>
        </div>
        <div class="site-preview-frame" inert>
            <iframe src="{{ route('home') }}" title="Vista previa de la portada"
                tabindex="-1" sandbox referrerpolicy="no-referrer"></iframe>
        </div>
        <p>Esta es la misma portada pública, reducida para caber en el panel.</p>
    </aside>
</div>
@endsection
