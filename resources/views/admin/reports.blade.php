@extends('layouts.admin')

@section('title', 'Reportes')
@section('heading', 'Rendimiento comercial')
@section('eyebrow', 'Visión ejecutiva')

@section('content')
<section class="report-summary">
    <article><span>Ventas ganadas</span><strong>US$ {{ number_format($wonRevenue) }}</strong><small>Valor acumulado</small></article>
    <article><span>Pronóstico ponderado</span><strong>US$ {{ number_format($forecast) }}</strong><small>Según probabilidad</small></article>
    <article><span>Prospectos captados</span><strong>{{ $leads->sum('total') }}</strong><small>Todos los canales</small></article>
</section>
<div class="report-grid">
    <section class="panel">
        <div class="panel-heading"><div><span>Embudo</span><h2>Oportunidades por etapa</h2></div></div>
        <div class="bar-chart">
            @php($maxDeals = max(1, $deals->max('total')))
            @foreach($deals as $item)
                <div><span>{{ \App\Support\CrmLabels::get($item->stage) }}</span><i><b style="width:{{ ($item->total / $maxDeals) * 100 }}%"></b></i><strong>{{ $item->total }}</strong></div>
            @endforeach
        </div>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><span>Adquisición</span><h2>Prospectos por origen</h2></div></div>
        <div class="source-list">
            @php($leadTotal = max(1, $leads->sum('total')))
            @foreach($leads as $item)
                <p><span class="source-icon">{{ strtoupper(substr($item->source, 0, 1)) }}</span>
                    <strong>{{ \App\Support\CrmLabels::get($item->source) }}</strong>
                    <i>{{ round($item->total / $leadTotal * 100) }}%</i><b>{{ $item->total }}</b></p>
            @endforeach
        </div>
    </section>
    <section class="panel report-wide">
        <div class="panel-heading"><div><span>Inventario</span><h2>Estado de propiedades</h2></div></div>
        <div class="inventory-stats">
            @foreach($inventory as $item)
                <article><span class="status-pill status-{{ $item->status }}">{{ \App\Support\CrmLabels::get($item->status) }}</span>
                    <strong>{{ $item->total }}</strong><small>propiedades</small></article>
            @endforeach
        </div>
    </section>
</div>
@endsection
