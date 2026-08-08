@extends('layouts.admin')

@section('title', 'Notificaciones')
@section('heading', 'Notificaciones y recordatorios')
@section('eyebrow', 'Automatización del CRM')

@php
    $cards = [
        'follow_up' => [
            'title' => 'Clientes por contactar',
            'lead' => 'Avisa cuando llega la fecha de próximo contacto que agendaste y, una vez al día, con la lista completa de quienes llevan tiempo sin actividad.',
            'daysLabel' => 'Considerar sin actividad después de',
            'daysSuffix' => 'días sin llamada ni mensaje registrado',
            'digest' => 'Lista diaria de clientes',
        ],
        'appointment' => [
            'title' => 'Agenda (citas y visitas)',
            'lead' => 'Cosas con hora de inicio: visitas, reuniones, firmas. Cada cita avisa antes de empezar y en el momento exacto.',
            'daysLabel' => 'El resumen abarca los próximos',
            'daysSuffix' => 'días de agenda',
            'digest' => 'Resumen de la agenda',
        ],
        'task' => [
            'title' => 'Tareas (pendientes por hacer)',
            'lead' => 'Cosas por completar, con o sin fecha límite. Las que tienen fecha avisan antes de vencer, al vencer y mientras sigan vencidas.',
            'daysLabel' => 'El resumen abarca los próximos',
            'daysSuffix' => 'días de vencimientos',
            'digest' => 'Resumen de tareas',
        ],
    ];
@endphp

@section('content')
<div class="notification-intro">
    <div>
        <strong>Cada aviso dice qué es, con quién y para cuándo</strong>
        <p>Los avisos por registro salen en el momento (antes de empezar, al empezar y si algo quedó vencido). Los resúmenes salen una sola vez al día a la hora que fijes aquí abajo.</p>
        @php($stale = ! $lastRun || $lastRun->lt(now()->subMinutes(15)))
        <p class="scheduler-heartbeat @if($stale) scheduler-stale @endif">
            @if($lastRun)
                Última revisión automática: <strong>{{ $lastRun->timezone($setting->timezone)->format('d/m/Y H:i') }}</strong>
                ({{ \App\Support\HumanDate::distance($lastRun, $setting->timezone) }}).
                @if($stale) El servidor no está revisando cada minuto: pídele a tu hosting que el cron de Laravel corra <code>* * * * *</code>, o los avisos llegarán todos juntos. @endif
            @else
                Todavía no se ha ejecutado la revisión automática. Si esto no cambia en unos minutos, el cron del hosting no está configurado.
            @endif
        </p>
    </div>
    <div class="notification-actions">
        <form method="post" action="{{ route('admin.notifications.test') }}">
            @csrf
            <button class="button button-ghost-dark" type="submit">Enviar prueba</button>
        </form>
        <form method="post" action="{{ route('admin.notifications.run') }}">
            @csrf
            <button class="button button-ghost-dark" type="submit">Revisar ahora</button>
        </form>
    </div>
</div>

<form class="notification-form" method="post" data-dirty-form
    action="{{ route('admin.notifications.update') }}">
    @csrf @method('put')
    <section class="form-card notification-general">
        <div class="form-card-heading">
            <div><h2>Entrega</h2><p>A qué buzones llega el correo y en qué horario se calculan todos los avisos.</p></div>
            <span>{{ $devices }} {{ $devices === 1 ? 'celular' : 'celulares' }} con la app</span>
        </div>
        @if($errors->any())
            <div class="form-error"><strong>Revisa la información:</strong> {{ $errors->first() }}</div>
        @endif
        <div class="form-grid">
            <label class="field"><span>Correos destinatarios</span><textarea name="recipient_emails" rows="4" required placeholder="correo1@dominio.com&#10;correo2@dominio.com">{{ old('recipient_emails', implode("\n", $setting->recipients())) }}</textarea><small class="field-help">Uno por línea o separados por comas. Máximo 10 correos.</small></label>
            <label class="field"><span>Zona horaria</span><select name="timezone">
                @foreach($timezones as $value => $label)<option value="{{ $value }}" @selected(old('timezone', $setting->timezone) === $value)>{{ $label }}</option>@endforeach
            </select><small class="field-help">Las horas de abajo se interpretan en esta zona.</small></label>
        </div>
        <div class="overdue-options">
            <label class="notification-switch">
                <input type="checkbox" name="overdue_enabled" value="1"
                    @checked(old('overdue_enabled', $setting->overdue_enabled))>
                <span aria-hidden="true"></span>
            </label>
            <div><strong>Seguir avisando lo vencido</strong><small>Cuando una tarea o cita pasa su fecha sin cerrarse, se repite el aviso una vez al día indicando que ya venció.</small></div>
            <label class="field"><span>Insistir hasta</span>
                <span class="input-suffix">
                    <input type="number" min="0" max="60" name="overdue_days"
                        value="{{ old('overdue_days', $setting->overdue_days ?? 3) }}">
                    <small>días después del vencimiento</small>
                </span>
            </label>
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
