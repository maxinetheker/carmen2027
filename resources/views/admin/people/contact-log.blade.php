@php
    $logs = $record->contactLogs()->with('user')->limit(30)->get();
    $storeRoute = route("admin.{$route}.logs.store", $record);
@endphp
<section class="form-card contact-log-card">
    <div class="form-card-heading">
        <div>
            <h2>Registro de contacto</h2>
            <p>Cada llamada, WhatsApp o correo que anotes aquí actualiza «Último contacto» y reinicia el conteo de días sin actividad.</p>
        </div>
        <span>{{ $logs->count() }} {{ $logs->count() === 1 ? 'registro' : 'registros' }}</span>
    </div>

    @if($errors->hasBag('contact-log'))
        <div class="form-error"><strong>Revisa el registro:</strong> {{ $errors->getBag('contact-log')->first() }}</div>
    @endif

    <form class="contact-log-form" method="post" action="{{ $storeRoute }}">
        @csrf
        <label class="field"><span>Medio</span>
            <select name="channel">
                @foreach(\App\Models\ContactLog::CHANNELS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>Sentido</span>
            <select name="direction">
                <option value="outgoing">Yo contacté</option>
                <option value="incoming">Me contactaron</option>
            </select>
        </label>
        <label class="field"><span>Resultado</span>
            <select name="outcome">
                <option value="">Sin especificar</option>
                @foreach(\App\Models\ContactLog::OUTCOMES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>Cuándo fue</span>
            <input type="datetime-local" name="contacted_at" value="{{ now()->format('Y-m-d\TH:i') }}">
        </label>
        <label class="field"><span>Volver a contactar</span>
            <input type="datetime-local" name="next_contact_at">
            <small class="field-help">Opcional. Programa el aviso del próximo llamado.</small>
        </label>
        <label class="field field-wide"><span>Qué se habló</span>
            <textarea name="notes" rows="2" placeholder="Ej.: le interesó el dúplex de Miraflores, pide fotos del estacionamiento."></textarea>
        </label>
        <button class="button button-accent" type="submit">Registrar contacto</button>
    </form>

    @if($logs->isEmpty())
        <p class="contact-log-empty">Todavía no hay contactos registrados con esta persona.</p>
    @else
        <ol class="contact-log-list">
            @foreach($logs as $log)
                <li class="contact-log-item contact-log-{{ $log->channel }}">
                    <div>
                        <strong>{{ $log->channel_label }}</strong>
                        <span class="contact-log-meta">
                            {{ $log->direction === 'incoming' ? 'Entrante' : 'Saliente' }}
                            · {{ \App\Support\HumanDate::short($log->contacted_at) }}
                            @if($log->outcome_label) · {{ $log->outcome_label }} @endif
                            @if($log->duration_label) · {{ $log->duration_label }} @endif
                            @if($log->source === 'call_log') · importado del celular @endif
                        </span>
                        @if($log->notes)<p>{{ $log->notes }}</p>@endif
                        @if($log->device_contact_name)
                            <small class="contact-log-device">Guardado en el celular como «{{ $log->device_contact_name }}»</small>
                        @endif
                    </div>
                    <form method="post" action="{{ route('admin.contact-logs.destroy', $log) }}"
                        data-confirm="¿Eliminar este registro de contacto?">
                        @csrf @method('delete')
                        <button class="danger-link" title="Eliminar registro">×</button>
                    </form>
                </li>
            @endforeach
        </ol>
    @endif
</section>
