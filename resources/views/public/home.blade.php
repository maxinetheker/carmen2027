@extends('layouts.public')

@section('title', $settings['seo_title'] ?? 'Carmen Mestanza · Tu asesora inmobiliaria de confianza en Lima')
@section('description', $settings['seo_description'] ?? 'Compra, vende o alquila propiedades en Lima con Carmen Mestanza, tu asesora de confianza. Acompañamiento cercano, estrategia y claridad de principio a fin.')
@section('canonical', route('home'))
@section('image', $settings['seo_image_path'] ?? '/og-blue-red.png')
@section('image_alt', 'Carmen Mestanza · Asesoría inmobiliaria en Lima')
@section('og_type', 'website')

@section('content')
<main>
    @include('public.partials.hero')

    <section class="trust-strip" aria-label="Indicadores">
        <p><strong>{{ $stats['years'] }}+</strong><span>Años acompañando decisiones</span></p>
        <p><strong>{{ $stats['clients'] }}+</strong><span>Clientes satisfechos</span></p>
        <p><strong>{{ $stats['properties'] }}</strong><span>Oportunidades seleccionadas</span></p>
        <p><strong>1:1</strong><span>Asesoría personalizada</span></p>
    </section>

    <section class="section section-properties">
        <div class="section-heading">
            <div><span class="eyebrow">Selección de Carmen</span>
                <h2>Propiedades que vale la pena mirar.</h2></div>
            <a class="text-link" href="{{ route('properties.index') }}">Ver todas <span>→</span></a>
        </div>
        <div class="property-grid">
            @forelse($featured as $property)
                @include('public.partials.property-card', compact('property'))
            @empty
                <div class="empty-state">Publica propiedades desde el panel CRM.</div>
            @endforelse
        </div>
    </section>

    <section class="section services-section" id="servicios">
        <div class="services-intro">
            <span class="eyebrow eyebrow-light">Una asesoría de principio a fin</span>
            <h2>No se trata solo de inmuebles. Se trata de decisiones bien tomadas.</h2>
            <p>Un método claro, información útil y negociación estratégica en cada etapa.</p>
        </div>
        <div class="service-list">
            <article><span>01</span><div><h3>Compra inteligente</h3><p>Búsqueda curada, análisis comparativo, visitas y negociación.</p></div></article>
            <article><span>02</span><div><h3>Venta estratégica</h3><p>Valoración, presentación, difusión y cierre con seguimiento.</p></div></article>
            <article><span>03</span><div><h3>Alquiler sin fricción</h3><p>Validación, contratos y coordinación para ambas partes.</p></div></article>
            <article><span>04</span><div><h3>Inversión inmobiliaria</h3><p>Lectura de rentabilidad, riesgo y potencial de valorización.</p></div></article>
        </div>
    </section>

    @include('public.partials.ceo')
    @include('public.partials.contact')
</main>
@endsection
