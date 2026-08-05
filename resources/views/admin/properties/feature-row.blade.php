<div class="feature-editor-row">
    <span class="feature-icon-preview material-symbols-rounded" data-feature-icon>{{ $feature['icon'] ?? 'info' }}</span>
    <label class="field"><span>Icono</span><select name="features[{{ $index }}][icon]">
        @foreach($icons as $value => $label)
            <option value="{{ $value }}" @selected(($feature['icon'] ?? 'info') === $value)>{{ $label }}</option>
        @endforeach
    </select></label>
    <label class="field"><span>Nombre</span><input name="features[{{ $index }}][label]" value="{{ $feature['label'] ?? '' }}" placeholder="Ej. Zonificación"></label>
    <label class="field"><span>Información</span><input name="features[{{ $index }}][value]" value="{{ $feature['value'] ?? '' }}" placeholder="Ej. CV"></label>
    <button class="feature-remove" type="button" data-feature-remove aria-label="Eliminar característica">×</button>
</div>
