@php
    $latitude = number_format((float) $property->latitude, 7, '.', '');
    $longitude = number_format((float) $property->longitude, 7, '.', '');
    $googleMapsUrl = 'https://www.google.com/maps/search/?'.http_build_query([
        'api' => 1, 'query' => $latitude.','.$longitude,
    ]);
    $embedUrl = $googleMapsKey
        ? 'https://www.google.com/maps/embed/v1/place?'.http_build_query([
            'key' => $googleMapsKey, 'q' => $latitude.','.$longitude,
            'zoom' => 16, 'language' => 'es', 'region' => 'PE',
        ])
        : 'https://www.openstreetmap.org/export/embed.html?'.http_build_query([
            'bbox' => ($longitude - .008).','.($latitude - .005).','.($longitude + .008).','.($latitude + .005),
            'layer' => 'mapnik', 'marker' => $latitude.','.$longitude,
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
        @if(! $googleMapsKey)
            <span class="map-provider-note">Vista cartográfica · abre Google Maps para navegación</span>
        @endif
    </div>
</section>
