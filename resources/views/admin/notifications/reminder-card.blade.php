<section class="form-card notification-card" data-reminder-card>
    <div class="notification-card-heading">
        <label class="notification-switch">
            <input type="checkbox" name="{{ $prefix }}_enabled" value="1"
                @checked(old("{$prefix}_enabled", $setting->getAttribute("{$prefix}_enabled")))>
            <span aria-hidden="true"></span>
        </label>
        <div><h2>{{ $card[0] }}</h2><p>{{ $card[1] }}</p></div>
    </div>
    <div class="notification-fields">
        <label class="field"><span>Frecuencia</span>
            <select name="{{ $prefix }}_frequency" data-frequency>
                <option value="daily" @selected(old("{$prefix}_frequency", $setting->getAttribute("{$prefix}_frequency")) === 'daily')>Diario</option>
                <option value="weekly" @selected(old("{$prefix}_frequency", $setting->getAttribute("{$prefix}_frequency")) === 'weekly')>Semanal</option>
            </select>
        </label>
        <label class="field"><span>Hora de envío</span>
            <input type="time" name="{{ $prefix }}_time" value="{{ old("{$prefix}_time", $setting->getAttribute("{$prefix}_time")) }}">
        </label>
        <label class="field" data-weekday><span>Día de la semana</span>
            <select name="{{ $prefix }}_weekday">
                @foreach($weekdays as $value => $label)
                    <option value="{{ $value }}" @selected((int) old("{$prefix}_weekday", $setting->getAttribute("{$prefix}_weekday")) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="field notification-days"><span>{{ $card[2] }}</span>
            <span class="input-suffix"><input type="number" min="0" max="365" name="{{ $prefix }}_days" value="{{ old("{$prefix}_days", $setting->getAttribute("{$prefix}_days")) }}"><small>{{ $card[3] }}</small></span>
        </label>
    </div>
    @if(in_array($prefix, ['appointment', 'task']))
        <div class="immediate-options">
            <label class="notification-switch">
                <input type="checkbox" name="{{ $prefix }}_immediate_enabled" value="1"
                    @checked(old("{$prefix}_immediate_enabled", $setting->getAttribute("{$prefix}_immediate_enabled")))>
                <span aria-hidden="true"></span>
            </label>
            <div><strong>Aviso inmediato</strong><small>Enviar un correo antes de la hora exacta.</small></div>
            <label class="field"><span>Anticipación</span>
                <select name="{{ $prefix }}_lead_minutes">
                    @foreach([5, 15, 30, 60, 120, 1440] as $minutes)
                        <option value="{{ $minutes }}" @selected((int) old("{$prefix}_lead_minutes", $setting->getAttribute("{$prefix}_lead_minutes")) === $minutes)>{{ $minutes < 60 ? $minutes.' minutos' : ($minutes === 1440 ? '1 día' : ($minutes / 60).' horas') }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    @endif
    <p class="last-delivery">Última revisión: <strong>{{ $setting->getAttribute("{$prefix}_last_sent_at")?->timezone($setting->timezone)->format('d/m/Y H:i') ?? 'Todavía no ejecutado' }}</strong></p>
</section>
