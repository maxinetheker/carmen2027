@php
    $latitude = old('latitude', $record->latitude);
    $longitude = old('longitude', $record->longitude);
@endphp
<section class="form-card location-editor-card">
    <div class="form-card-heading">
        <div><h2>Ubicación en el mapa</h2><p>Haz clic o arrastra el marcador hasta la ubicación exacta del inmueble.</p></div>
        <span class="location-status-badge">Mapa interactivo</span>
    </div>
    <div class="location-picker" data-location-picker data-latitude="{{ $latitude }}"
        data-longitude="{{ $longitude }}" data-default-latitude="-12.0464"
        data-default-longitude="-77.0428">
        <div class="location-map" data-location-map aria-label="Selector de ubicación"></div>
        <input type="hidden" name="latitude" value="{{ $latitude }}" data-location-latitude>
        <input type="hidden" name="longitude" value="{{ $longitude }}" data-location-longitude>
        <div class="location-picker-footer">
            <p data-location-status>{{ $latitude && $longitude ? 'Ubicación seleccionada' : 'Selecciona un punto en el mapa' }}</p>
            <div>
                <button class="mini-button" type="button" data-location-current>Usar mi ubicación</button>
                <button class="mini-button mini-button-muted" type="button" data-location-clear>Quitar ubicación</button>
            </div>
        </div>
    </div>
</section>
