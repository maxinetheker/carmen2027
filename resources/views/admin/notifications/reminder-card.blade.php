@php($timed = in_array($prefix, ['appointment', 'task'], true))
<section class="form-card notification-card" data-reminder-card>
    <div class="notification-card-heading">
        <label class="notification-switch">
            <input type="checkbox" name="{{ $prefix }}_enabled" value="1"
                @checked(old("{$prefix}_enabled", $setting->getAttribute("{$prefix}_enabled")))>
            <span aria-hidden="true"></span>
        </label>
        <div><h2>{{ $card['title'] }}</h2><p>{{ $card['lead'] }}</p></div>
    </div>

    <fieldset class="channel-picker">
        <legend>¿Por dónde quieres recibirlo?</legend>
        <label class="channel-option">
            <input type="checkbox" name="{{ $prefix }}_email_enabled" value="1"
                @checked(old("{$prefix}_email_enabled", $setting->getAttribute("{$prefix}_email_enabled")))>
            <span>✉️ Correo</span>
        </label>
        <label class="channel-option">
            <input type="checkbox" name="{{ $prefix }}_push_enabled" value="1"
                @checked(old("{$prefix}_push_enabled", $setting->getAttribute("{$prefix}_push_enabled")))>
            <span>📱 Notificación en la app</span>
        </label>
        <small>Si desmarcas los dos, este tipo de aviso deja de enviarse.</small>
    </fieldset>

    @if($timed)
        <div class="immediate-options">
            <label class="notification-switch">
                <input type="checkbox" name="{{ $prefix }}_immediate_enabled" value="1"
                    @checked(old("{$prefix}_immediate_enabled", $setting->getAttribute("{$prefix}_immediate_enabled")))>
                <span aria-hidden="true"></span>
            </label>
            <div><strong>Avisar antes</strong><small>Un aviso de anticipación por cada {{ $prefix === 'task' ? 'tarea' : 'cita' }} que tenga activado «Avisarme».</small></div>
            <label class="field"><span>Anticipación por defecto</span>
                <select name="{{ $prefix }}_lead_minutes">
                    @foreach([5, 10, 15, 30, 60, 120, 1440] as $minutes)
                        <option value="{{ $minutes }}" @selected((int) old("{$prefix}_lead_minutes", $setting->getAttribute("{$prefix}_lead_minutes")) === $minutes)>{{ $minutes < 60 ? $minutes.' minutos' : ($minutes === 1440 ? '1 día' : ($minutes / 60).' horas') }}</option>
                    @endforeach
                </select>
                <small class="field-help">Cada registro puede usar la suya.</small>
            </label>
        </div>
        <div class="immediate-options">
            <label class="notification-switch">
                <input type="checkbox" name="{{ $prefix }}_exact_enabled" value="1"
                    @checked(old("{$prefix}_exact_enabled", $setting->getAttribute("{$prefix}_exact_enabled")))>
                <span aria-hidden="true"></span>
            </label>
            <div><strong>Avisar a la hora exacta</strong><small>Segundo aviso justo cuando {{ $prefix === 'task' ? 'vence la tarea' : 'empieza la cita' }}.</small></div>
            <label class="checkbox-inline">
                <input type="checkbox" name="{{ $prefix }}_notify_default" value="1"
                    @checked(old("{$prefix}_notify_default", $setting->getAttribute("{$prefix}_notify_default")))>
                <span>Activar «Avisarme» en {{ $prefix === 'task' ? 'las tareas nuevas' : 'las citas nuevas' }}</span>
            </label>
        </div>
    @endif

    <fieldset class="digest-block">
        <legend>{{ $card['digest'] }}</legend>
        <div class="notification-fields">
            <label class="field"><span>Frecuencia</span>
                <select name="{{ $prefix }}_frequency" data-frequency>
                    <option value="daily" @selected(old("{$prefix}_frequency", $setting->getAttribute("{$prefix}_frequency")) === 'daily')>Todos los días</option>
                    <option value="weekly" @selected(old("{$prefix}_frequency", $setting->getAttribute("{$prefix}_frequency")) === 'weekly')>Una vez por semana</option>
                </select>
            </label>
            <label class="field"><span>Hora de envío</span>
                <input type="time" name="{{ $prefix }}_time" value="{{ old("{$prefix}_time", $setting->getAttribute("{$prefix}_time")) }}">
                @if($timed)<small class="field-help">También es la hora de los avisos de vencido.</small>@endif
            </label>
            <label class="field" data-weekday><span>Día de la semana</span>
                <select name="{{ $prefix }}_weekday">
                    @foreach($weekdays as $value => $label)
                        <option value="{{ $value }}" @selected((int) old("{$prefix}_weekday", $setting->getAttribute("{$prefix}_weekday")) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field notification-days"><span>{{ $card['daysLabel'] }}</span>
                <span class="input-suffix"><input type="number" min="0" max="365" name="{{ $prefix }}_days" value="{{ old("{$prefix}_days", $setting->getAttribute("{$prefix}_days")) }}"><small>{{ $card['daysSuffix'] }}</small></span>
            </label>
        </div>
    </fieldset>

    <p class="last-delivery">Último resumen enviado: <strong>{{ $setting->getAttribute("{$prefix}_last_sent_at")?->timezone($setting->timezone)->format('d/m/Y H:i') ?? 'todavía ninguno' }}</strong></p>
</section>
