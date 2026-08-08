@extends('layouts.public')

@section('title', 'Propiedades · Carmen Mestanza')
@section('description', 'Departamentos, casas, oficinas y terrenos seleccionados en Lima. Encuentra propiedades en venta y alquiler con asesoría personalizada.')
@section('canonical', route('properties.index'))
@if(request()->query()) @section('robots', 'noindex,follow') @endif

@php
    $activeCount = collect($filters)->except(['sort', 'radius'])->filter(fn ($value) => $value !== null && $value !== '')->count();
    $priceSymbol = $filters['currency'] === 'PEN' ? 'S/' : 'US$';
@endphp

@section('content')
<main class="catalog-page">
    <section class="catalog-hero">
        <div><span class="eyebrow eyebrow-light">Colección inmobiliaria</span><h1>Encuentra el lugar<br>que encaja contigo.</h1><p>Propiedades verificadas, elegidas con criterio y acompañamiento personal.</p></div>
        <div class="catalog-hero-stat"><strong>{{ $properties->total() }}</strong><span>oportunidades disponibles</span></div>
    </section>

    <div class="catalog-browser">
        <aside class="catalog-sidebar">
            <details class="catalog-filter-drawer" open data-filter-drawer>
                <summary><span><i class="material-symbols-rounded">tune</i> Filtrar propiedades</span><small>{{ $activeCount ? $activeCount.' activos' : 'Mostrar opciones' }}</small></summary>
                <form id="catalog-filter-form" class="catalog-filter catalog-filter-pro" method="get" data-catalog-filters>
                    <div class="filter-fields-scroll">
                    <div class="catalog-filter-heading"><span>Tu búsqueda</span><h2>Encuentra tu propiedad</h2></div>
                    <label class="catalog-keyword"><span>¿Qué estás buscando?</span><div><i class="material-symbols-rounded">search</i><input name="q" value="{{ $filters['q'] }}" placeholder="Código, dirección..."></div></label>
                    <div class="filter-pair">
                        <label><span>Operación</span><select name="operation"><option value="">Venta o alquiler</option><option value="venta" @selected($filters['operation'] === 'venta')>Venta</option><option value="alquiler" @selected($filters['operation'] === 'alquiler')>Alquiler</option></select></label>
                        <label><span>Tipo</span><select name="type"><option value="">Todos</option>@foreach(\App\Models\Property::TYPES as $value => $label)<option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>@endforeach</select></label>
                    </div>
                    <label><span>Zona</span><select name="district"><option value="">Todo Lima</option>@foreach($zones as $zone)<option value="{{ $zone->district }}" @selected($filters['district'] === $zone->district)>{{ $zone->district }} ({{ $zone->properties_count }})</option>@endforeach</select></label>
                    <div class="filter-section">
                        <div class="filter-section-heading"><strong>Presupuesto</strong><label class="currency-compact"><span>Moneda</span><select name="currency"><option value="">Todas</option><option value="USD" @selected($filters['currency'] === 'USD')>US$</option><option value="PEN" @selected($filters['currency'] === 'PEN')>S/</option></select></label></div>
                        <div class="price-pair"><label><span>Desde</span><input type="number" min="0" name="min_price" value="{{ $filters['min_price'] }}" placeholder="{{ number_format($priceBounds->minimum ?? 0, 0, '.', '') }}"></label><label><span>Hasta</span><input type="number" min="0" name="max_price" value="{{ $filters['max_price'] }}" placeholder="{{ number_format($priceBounds->maximum ?? 0, 0, '.', '') }}"></label></div>
                    </div>
                    <input type="hidden" name="latitude" value="{{ $filters['latitude'] }}" data-nearby-latitude><input type="hidden" name="longitude" value="{{ $filters['longitude'] }}" data-nearby-longitude>
                    <div class="nearby-filter"><button type="button" data-nearby-search><i class="material-symbols-rounded">near_me</i>{{ $filters['latitude'] !== null ? 'Actualizar ubicación' : 'Buscar cerca de mí' }}</button><label><span>Distancia máxima</span><select name="radius">@foreach([3, 5, 10, 20, 40] as $radius)<option value="{{ $radius }}" @selected($filters['radius'] === $radius)>{{ $radius }} km</option>@endforeach</select></label><small data-nearby-status>{{ $filters['latitude'] !== null ? 'Ubicación activada' : 'Usaremos la ubicación de tu dispositivo' }}</small></div>
                    </div>
                    <div class="filter-actions-sticky"><button class="button button-dark filter-submit">Ver {{ $properties->total() }} propiedades</button>@if($activeCount)<a class="filter-clear" href="{{ route('properties.index') }}">Limpiar {{ $activeCount }} filtros</a>@endif</div>
                </form>
            </details>
        </aside>

        <div class="catalog-listing">
            @if($zones->isNotEmpty())<nav class="zone-shortcuts" aria-label="Zonas disponibles"><span>Zonas destacadas</span>@foreach($zones->take(6) as $zone)<a class="@if($filters['district'] === $zone->district) active @endif" href="{{ route('properties.index', array_merge(request()->except(['page', 'district']), ['district' => $zone->district])) }}">{{ $zone->district }} <small>{{ $zone->properties_count }}</small></a>@endforeach</nav>@endif
            <section class="catalog-results">
                <div class="catalog-results-heading"><div><span>Selección disponible</span><h2><strong>{{ $properties->total() }}</strong> propiedades encontradas</h2></div><label class="catalog-sort"><span>Ordenar por</span><select name="sort" form="catalog-filter-form" onchange="this.form.requestSubmit()">@foreach(['featured' => 'Recomendadas', 'newest' => 'Más recientes', 'price_asc' => 'Menor precio', 'price_desc' => 'Mayor precio', 'area_desc' => 'Mayor área'] as $value => $label)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>@endforeach</select></label></div>
                <div class="property-grid">@foreach($properties as $property) @include('public.partials.property-card', compact('property')) @endforeach</div>
                @if($properties->isEmpty())<div class="empty-state">No encontramos coincidencias. Amplía el precio, el radio o prueba otra zona.</div>@endif
                <div class="pagination">{{ $properties->links() }}</div>
            </section>
        </div>
    </div>
</main>
@endsection
