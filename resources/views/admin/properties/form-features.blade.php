<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Características visuales</h2><p>Datos legales, medidas y servicios con iconos claros.</p></div>
        <button class="mini-button" type="button" data-feature-add>+ Agregar</button>
    </div>
    <div class="feature-presets" aria-label="Características rápidas">
        @foreach($featurePresets as $icon => $label)
            <button type="button" data-feature-preset data-icon="{{ $icon }}" data-label="{{ $label }}">
                <span class="material-symbols-rounded">{{ $icon }}</span>{{ $label }}
            </button>
        @endforeach
    </div>
    <div class="feature-editor" data-feature-list>
        @foreach($features as $index => $feature)
            @include('admin.properties.feature-row', compact('index', 'feature', 'icons'))
        @endforeach
    </div>
    <template data-feature-template>
        @include('admin.properties.feature-row', ['index' => '__INDEX__', 'feature' => ['icon' => 'info', 'label' => '', 'value' => ''], 'icons' => $icons])
    </template>
</section>
