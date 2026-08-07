@php
    $latitude = number_format((float) $property->latitude, 7, '.', '');
    $longitude = number_format((float) $property->longitude, 7, '.', '');
    $googleMapsUrl = 'https://www.google.com/maps/search/?'.http_build_query([
        'api' => 1, 'query' => $latitude.','.$longitude,
    ]);
    // Keyless Google Maps embed on purpose: this project uses no Google API key, so
    // there is nothing to configure, no billing account and no quota to run out of.
    $embedUrl = 'https://maps.google.com/maps?'.http_build_query([
        'q' => $latitude.','.$longitude, 'z' => 16,
        'hl' => 'es', 'output' => 'embed',
    ]);
@endphp
<section class="property-map-section">
    <div class="property-map-heading">
        <div><span class="eyebrow">Ubicación</span><h2>Conoce la zona.</h2>
            <p>{{ $property->address ? $property->address.', ' : '' }}{{ $property->district }}, Lima</p></div>
        <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer">
            <span class="material-symbols-rounded">directions</span> Abrir en Google Maps
        </a>
    </div>
    <div class="property-map-frame">
        <iframe src="{{ $embedUrl }}" title="Mapa de {{ $property->title }}"
            loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
</section>
