@extends('layouts.public')

@php
    $safeDescription = app(\App\Support\RichTextSanitizer::class)->clean($property->description);
    $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(
        ['</p>', '</h2>', '</h3>', '<br>', '<br/>', '<br />'], ' ', (string) $safeDescription
    ))));
    $sharePrice = ($property->currency === 'USD' ? 'US$ ' : 'S/ ').number_format($property->price);
    $shareDescription = $property->operation_label.' · '.$property->type_label.' en '.$property->district
        .' · '.$sharePrice.' · '.number_format($property->area, 0).' m². '
        .Str::limit($plainDescription, 95);
    $propertySchema = [
        '@context' => 'https://schema.org', '@type' => 'RealEstateListing',
        'name' => $property->title,
        'description' => Str::limit($plainDescription, 300),
        'url' => route('properties.show', $property),
        'image' => \Illuminate\Support\Str::startsWith($property->cover_url, ['http://', 'https://']) ? $property->cover_url : url($property->cover_url),
        'datePosted' => $property->created_at?->toDateString(),
        'mainEntity' => [
            '@type' => 'Accommodation', 'name' => $property->title,
            'floorSize' => ['@type' => 'QuantitativeValue', 'value' => (float) $property->area, 'unitCode' => 'MTK'],
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $property->address, 'addressLocality' => $property->district, 'addressRegion' => 'Lima', 'addressCountry' => 'PE'],
            'offers' => ['@type' => 'Offer', 'price' => (float) $property->price, 'priceCurrency' => $property->currency, 'availability' => 'https://schema.org/InStock'],
        ],
    ];
    if ($property->latitude !== null && $property->longitude !== null) {
        $propertySchema['mainEntity']['geo'] = [
            '@type' => 'GeoCoordinates', 'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
        ];
    }
    if ((int) $property->bedrooms > 0) {
        $propertySchema['mainEntity']['numberOfBedrooms'] = (int) $property->bedrooms;
    }
    if ((float) $property->bathrooms > 0) {
        $propertySchema['mainEntity']['numberOfBathroomsTotal'] = (float) $property->bathrooms;
    }
@endphp

@section('title', $property->title.' · Carmen Mestanza')
@section('description', Str::limit(trim($shareDescription), 190))
@section('image', route('properties.share-image', $property))
@section('image_alt', $property->title.' en '.$property->district)
@section('image_type', 'image/jpeg')
@section('image_width', '1200')
@section('image_height', '630')
@section('canonical', route('properties.show', $property))
@section('og_type', 'product')

@push('structured-data')
<script type="application/ld+json">{!! json_encode($propertySchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<main class="property-detail">
    <section class="detail-heading">
        <div>
            <a class="back-link" href="{{ route('properties.index') }}">← Volver a propiedades</a>
            <span class="eyebrow">{{ $property->district }} · {{ $property->operation_label }}</span>
            <h1>{{ $property->title }}</h1>
        </div>
        <div class="detail-price">
            <span>{{ $property->code }}</span>
            <strong>{{ $property->currency === 'USD' ? 'US$' : 'S/' }} {{ number_format($property->price) }}</strong>
        </div>
    </section>

    @include('public.partials.property-gallery')

    <section class="detail-content">
        <div class="detail-main">
            <div class="property-feature-grid">
                <article><span class="material-symbols-rounded">square_foot</span><div><strong>{{ number_format($property->area, 2) }} m²</strong><small>Área total</small></div></article>
                @if((float) $property->bathrooms > 0)
                    <article><span class="material-symbols-rounded">bathtub</span><div><strong>{{ $property->bathrooms_label }}</strong><small>Baños</small></div></article>
                @endif
                @if((int) $property->bedrooms > 0)
                    <article><span class="material-symbols-rounded">bed</span><div><strong>{{ $property->bedrooms }}</strong><small>Dormitorios</small></div></article>
                @endif
                <article><span class="material-symbols-rounded">verified</span><div><strong>{{ $property->status_label }}</strong><small>Estado</small></div></article>
                @foreach($property->features as $feature)
                    <article><span class="material-symbols-rounded">{{ $feature->icon }}</span><div><strong>{{ $feature->value }}</strong><small>{{ $feature->label }}</small></div></article>
                @endforeach
            </div>

            <div class="property-story">
                <span class="eyebrow">Sobre esta propiedad</span>
                <h2>Un espacio para vivirlo a tu manera.</h2>
                <div class="rich-description" data-emoji-render>
                    @if(Str::contains((string) $safeDescription, '<'))
                        {!! $safeDescription !!}
                    @else
                        <p>{!! nl2br(e($safeDescription)) !!}</p>
                    @endif
                </div>
                @if($property->address)
                    <p class="detail-address"><span>Ubicación</span>{{ $property->address }}, {{ $property->district }}</p>
                @endif
            </div>
        </div>
        <aside class="detail-contact">
            <span class="avatar-logo"><img src="{{ asset('images/carmen-mestanza-logo.webp') }}" alt="Logo de Carmen Mestanza"></span>
            <h3>¿Te interesa esta propiedad?</h3>
            <p>Agenda una visita privada con Carmen y recibe la ficha completa.</p>
            <a class="button button-accent" href="https://wa.me/{{ $settings['whatsapp'] ?? '51987654321' }}?text={{ urlencode('Hola Carmen, me interesa '.$property->title.' ('.$property->code.')') }}">Consultar por WhatsApp</a>
            <a class="button button-ghost-dark" href="{{ route('home') }}#contacto">Dejar mis datos</a>
        </aside>
    </section>

    @if($property->latitude !== null && $property->longitude !== null)
        @include('public.partials.property-location')
    @endif

    @if($related->isNotEmpty())
        <section class="section related-section">
            <div class="section-heading"><h2>También en {{ $property->district }}</h2></div>
            <div class="property-grid">
                @foreach($related as $property)
                    @include('public.partials.property-card', compact('property'))
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection
