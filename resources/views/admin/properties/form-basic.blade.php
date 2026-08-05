<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Información comercial</h2><p>Datos principales del catálogo y reglas de publicación.</p></div>
        <span>{{ $record->exists ? '#'.$record->id : 'Nuevo inmueble' }}</span>
    </div>
    <div class="form-grid">
        <label class="field field-wide"><span>Título</span><input name="title" required value="{{ old('title', $record->title) }}"></label>
        <label class="field"><span>Código</span><input name="code" required value="{{ old('code', $record->code) }}"></label>
        <label class="field"><span>Distrito</span><input name="district" required value="{{ old('district', $record->district) }}"></label>
        <label class="field"><span>Tipo</span><select name="type" required>
            @foreach(['departamento' => 'Departamento', 'casa' => 'Casa', 'oficina' => 'Oficina', 'terreno' => 'Terreno'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $record->type) === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="field"><span>Operación</span><select name="operation" required>
            @foreach(['venta' => 'Venta', 'alquiler' => 'Alquiler'] as $value => $label)
                <option value="{{ $value }}" @selected(old('operation', $record->operation) === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="field"><span>Estado</span><select name="status" required>
            @foreach(['available' => 'Disponible', 'reserved' => 'Reservada', 'sold' => 'Vendida'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $record->status ?: 'available') === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="field"><span>Moneda</span><select name="currency" required>
            <option value="USD" @selected(old('currency', $record->currency ?: 'USD') === 'USD')>Dólares (USD)</option>
            <option value="PEN" @selected(old('currency', $record->currency) === 'PEN')>Soles (PEN)</option>
        </select></label>
        <label class="field"><span>Precio</span><input type="number" min="0" step="0.01" name="price" required value="{{ old('price', $record->price) }}"></label>
        <label class="field"><span>Área m²</span><input type="number" min="1" step="0.01" name="area" required value="{{ old('area', $record->area) }}"></label>
        <label class="field"><span>Dormitorios</span><input type="number" min="0" name="bedrooms" required value="{{ old('bedrooms', $record->bedrooms ?? 0) }}"></label>
        <label class="field"><span>Baños</span><input type="number" min="0" step="0.5" name="bathrooms" required value="{{ old('bathrooms', $record->bathrooms ?? 0) }}"></label>
        <label class="field field-wide"><span>Dirección</span><input name="address" value="{{ old('address', $record->address) }}"></label>
        <label class="field"><span>Prioridad</span><input type="number" min="0" max="999" name="priority" required value="{{ old('priority', $record->priority ?? 50) }}"><small>Mayor número = aparece primero.</small></label>
        <div class="publishing-options field-wide">
            <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $record->exists ? $record->is_published : true))><span>Publicada en la web</span></label>
            <label><input type="checkbox" name="featured" value="1" @checked(old('featured', $record->featured))><span>Propiedad destacada</span></label>
            <label><input type="checkbox" name="show_in_hero" value="1" @checked(old('show_in_hero', $record->show_in_hero))><span>Mostrar en el carrusel principal</span></label>
        </div>
    </div>
</section>
