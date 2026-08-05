@extends('layouts.admin')

@section('title', 'Panel general')
@section('heading', 'Buenos días, '.strtok(auth()->user()->name, ' '))
@section('eyebrow', now()->translatedFormat('l, d \d\e F'))

@section('content')
<div class="dashboard-intro">
    <p>Aquí tienes el pulso de tu negocio inmobiliario.</p>
    <div><a class="quick-link" href="{{ route('admin.leads.create') }}">+ Nuevo prospecto</a>
        <a class="quick-link" href="{{ route('admin.appointments.create') }}">+ Agendar cita</a></div>
</div>

<section class="metric-grid">
    @foreach($metrics as $index => $metric)
        <article class="metric-card">
            <span class="metric-icon">{{ ['◎','⌂','◇','↗'][$index] }}</span>
            <p>{{ $metric['label'] }}</p>
            <strong>{{ $metric['value'] }}</strong>
            <small>{{ $metric['trend'] }}</small>
        </article>
    @endforeach
</section>

<div class="dashboard-grid">
    <section class="panel panel-wide">
        <div class="panel-heading">
            <div><span>Flujo comercial</span><h2>Oportunidades activas</h2></div>
            <a href="{{ route('admin.deals.index') }}">Ver oportunidades →</a>
        </div>
        @include('admin.partials.pipeline')
    </section>
    <section class="panel">
        <div class="panel-heading">
            <div><span>Próximamente</span><h2>Agenda</h2></div>
            <a href="{{ route('admin.appointments.index') }}">Ver todo</a>
        </div>
        <div class="agenda-list">
            @forelse($appointments as $appointment)
                <article>
                    <time><strong>{{ $appointment->starts_at->format('d') }}</strong><span>{{ $appointment->starts_at->translatedFormat('M') }}</span></time>
                    <div><h3>{{ $appointment->title }}</h3><p>{{ $appointment->starts_at->format('H:i') }} · {{ $appointment->location }}</p></div>
                    <span class="status-pill status-{{ $appointment->status }}">{{ \App\Support\CrmLabels::get($appointment->status) }}</span>
                </article>
            @empty
                <div class="empty-state compact">Sin citas próximas.</div>
            @endforelse
        </div>
    </section>
    <section class="panel">
        <div class="panel-heading">
            <div><span>En foco</span><h2>Tareas pendientes</h2></div>
            <a href="{{ route('admin.tasks.index') }}">Ver todo</a>
        </div>
        @include('admin.partials.tasks')
    </section>
    <section class="panel">
        <div class="panel-heading">
            <div><span>Historial</span><h2>Actividad reciente</h2></div>
        </div>
        @include('admin.partials.activities')
    </section>
</div>
@endsection
