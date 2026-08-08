<section class="hero">
    {{-- La firma (logo + «Hablas directamente con Carmen») va fuera del bloque que
         se encoge: por largo que sea el título, nunca queda fuera de la pantalla. --}}
    <div class="hero-copy">
        <div class="hero-headline">
            <span class="eyebrow">{{ $settings['hero_eyebrow'] ?? 'Carmen Mestanza · Experiencia y cercanía en Lima' }}</span>
            <h1>{{ $settings['hero_title'] ?? 'Tu asesora inmobiliaria de confianza, en cada decisión.' }}</h1>
            <p>{{ $settings['hero_subtitle'] ?? 'Compra, vende o alquila con información clara, estrategia y acompañamiento directo de principio a fin.' }}</p>
            <div class="hero-actions">
                <a class="button button-accent" href="{{ route('properties.index') }}">Explorar propiedades</a>
                <a class="button button-ghost" href="#contacto">Quiero asesoría</a>
            </div>
        </div>
        <div class="hero-note">
            <span class="avatar-logo"><img src="{{ asset('images/carmen-mestanza-logo.webp') }}" alt="Logo de Carmen Mestanza"></span>
            <p><strong>Hablas directamente con Carmen</strong><small>Confianza y acompañamiento en cada etapa</small></p>
        </div>
    </div>
    <div class="hero-visual" data-hero-carousel tabindex="0"
        role="region" aria-roledescription="carrusel" aria-label="Propiedades destacadas">
        @forelse($heroProperties ?? [] as $index => $heroProperty)
            @php
                $coverMedia = $heroProperty->media->firstWhere('is_cover', true)
                    ?? $heroProperty->media->firstWhere('type', 'image');
                $isNarrow = $coverMedia?->width && $coverMedia?->height
                    && ($coverMedia->width / $coverMedia->height) < .82;
            @endphp
            <article class="hero-slide @if($index === 0) active @endif @if($isNarrow) has-narrow-image @endif"
                data-hero-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                @if($isNarrow)
                    <img class="hero-slide-backdrop" src="{{ $heroProperty->cover_url }}" alt="" aria-hidden="true">
                @endif
                <img class="hero-slide-image" src="{{ $heroProperty->cover_url }}" alt="{{ $heroProperty->title }}" draggable="false"
                    @if($index) loading="lazy" @else fetchpriority="high" @endif>
                <a class="hero-card" href="{{ route('properties.show', $heroProperty) }}">
                    <span>{{ $heroProperty->operation_label }} · {{ $heroProperty->district }}</span>
                    <strong>{{ $heroProperty->title }}</strong>
                    <p>{{ number_format($heroProperty->area) }} m²
                        @if($heroProperty->bedrooms) · {{ $heroProperty->bedrooms }} dorm. @endif
                        · {{ $heroProperty->currency === 'USD' ? 'US$' : 'S/' }} {{ number_format($heroProperty->price) }}</p>
                </a>
            </article>
        @empty
            <article class="hero-slide active" data-hero-slide aria-hidden="false">
                <img class="hero-slide-image" src="/images/property-1.jpg" alt="Interior contemporáneo de una propiedad seleccionada" draggable="false">
            </article>
        @endforelse
        @if(($heroProperties ?? collect())->count() > 1)
            <div class="hero-carousel-controls" aria-label="Propiedades principales">
                <button type="button" data-hero-previous aria-label="Propiedad anterior">←</button>
                <div>
                    @foreach($heroProperties as $index => $heroProperty)
                        <button type="button" data-hero-dot="{{ $index }}"
                            class="@if($index === 0) active @endif"
                            aria-label="Ver {{ $heroProperty->title }}"></button>
                    @endforeach
                </div>
                <button type="button" data-hero-next aria-label="Propiedad siguiente">→</button>
            </div>
        @endif
        <div class="hero-seal"><strong>{{ $stats['years'] }}+</strong><span>años de<br>experiencia</span></div>
    </div>
</section>

<form class="property-search" method="get" action="{{ route('properties.index') }}">
    <label><span>Operación</span>
        <select name="operation"><option value="">Comprar o alquilar</option><option value="venta">Comprar</option><option value="alquiler">Alquilar</option></select>
    </label>
    <label><span>Tipo</span>
        <select name="type"><option value="">Todos los inmuebles</option>@foreach(\App\Models\Property::TYPES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    </label>
    <label><span>Distrito</span>
        <input name="district" placeholder="Ej. Miraflores">
    </label>
    <button class="search-button" aria-label="Buscar propiedades">Buscar <span>→</span></button>
</form>
