<article class="property-card">
    <a class="property-image" href="{{ route('properties.show', $property) }}">
        <img src="{{ $property->cover_url }}" alt="{{ $property->title }}" loading="lazy">
        <span class="property-tag">{{ $property->operation_label }}</span>
    </a>
    <button type="button" class="favorite-button" aria-label="Guardar propiedad" aria-pressed="false">♡</button>
    <div class="property-content">
        <span class="property-location">{{ $property->district }} · {{ $property->type_label }}</span>
        <h3><a href="{{ route('properties.show', $property) }}">{{ $property->title }}</a></h3>
        <div class="property-meta">
            <span><i class="material-symbols-rounded">square_foot</i>{{ number_format($property->area) }} m²</span>
            @if((int) $property->bedrooms > 0)<span><i class="material-symbols-rounded">bed</i>{{ $property->bedrooms }}</span>@endif
            @if((float) $property->bathrooms > 0)<span><i class="material-symbols-rounded">bathtub</i>{{ $property->bathrooms_label }}</span>@endif
        </div>
        <div class="property-price">
            <div><small>Desde</small><strong>{{ $property->currency === 'USD' ? 'US$' : 'S/' }} {{ number_format($property->price) }}</strong></div>
            <a href="{{ route('properties.show', $property) }}" aria-label="Ver {{ $property->title }}">
                <span class="material-symbols-rounded">arrow_outward</span>
            </a>
        </div>
    </div>
</article>
