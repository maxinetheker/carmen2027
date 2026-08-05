@extends('layouts.admin')

@section('title', 'Notificaciones')
@section('heading', 'Notificaciones y recordatorios')
@section('eyebrow', 'Automatización del CRM')

@php
    $cards = [
        'follow_up' => ['Clientes por contactar', 'Detecta prospectos sin contacto reciente.', 'Contactar después de', 'días sin actividad'],
        'appointment' => ['Agenda y reuniones', 'Resume visitas, llamadas y reuniones próximas.', 'Revisar los próximos', 'días de agenda'],
        'task' => ['Tareas pendientes', 'Incluye tareas vencidas o cercanas a vencer.', 'Revisar los próximos', 'días de tareas'],
    ];
@endphp

@section('content')
<div class="notification-intro">
    <div><strong>Avisos claros, cuando corresponden</strong><p>El scheduler respeta la frecuencia global y el estado Activo, Pausado o No contactar de cada persona.</p></div>
    <form method="post" action="{{ route('admin.notifications.run') }}">
        @csrf
        <button class="button button-ghost-dark" type="submit">Procesar ahora</button>
    </form>
</div>

<form class="notification-form" method="post" data-dirty-form
    action="{{ route('admin.notifications.update') }}">
    @csrf @method('put')
    <section class="form-card notification-general">
        <div class="form-card-heading">
            <div><h2>Entrega de correos</h2><p>Define el buzón y la zona horaria usados por todos los avisos.</p></div>
            <span>General</span>
        </div>
        @if($errors->any())
            <div class="form-error"><strong>Revisa la información:</strong> {{ $errors->first() }}</div>
        @endif
        <div class="form-grid">
            <label class="field"><span>Correos destinatarios</span><textarea name="recipient_emails" rows="4" required placeholder="correo1@dominio.com&#10;correo2@dominio.com">{{ old('recipient_emails', implode("\n", $setting->recipients())) }}</textarea><small class="field-help">Uno por línea o separados por comas. Máximo 10 correos.</small></label>
            <label class="field"><span>Zona horaria</span><select name="timezone">
                @foreach($timezones as $value => $label)<option value="{{ $value }}" @selected(old('timezone', $setting->timezone) === $value)>{{ $label }}</option>@endforeach
            </select></label>
        </div>
    </section>

    <div class="notification-grid">
        @foreach($cards as $prefix => $card)
            @include('admin.notifications.reminder-card', compact('prefix', 'card'))
        @endforeach
    </div>
    <div class="form-actions">
        <button class="button button-accent" type="submit">Guardar configuración</button>
    </div>
    <div class="fixed-save-bar mobile-record-save" data-save-bar @if(!$errors->any()) hidden @endif>
        <span><strong>Cambios sin guardar</strong><small>Actualiza los recordatorios.</small></span>
        <button class="button button-accent" type="submit" data-save-button>Guardar</button>
    </div>
</form>
@endsection
