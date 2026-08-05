<section class="section contact-section" id="contacto">
    <div class="contact-copy">
        <span class="eyebrow eyebrow-light">El siguiente paso</span>
        <h2>Cuéntame qué estás buscando.</h2>
        <p>Déjame tus datos y conversemos sin compromiso sobre tu objetivo inmobiliario.</p>
        <div class="contact-details">
            <p><span>Teléfono / WhatsApp</span><strong>{{ $settings['phone'] ?? '+51 987 654 321' }}</strong></p>
            <p><span>Correo</span><strong>{{ $settings['email'] ?? 'carmen@example.com' }}</strong></p>
        </div>
    </div>
    <form class="contact-form" action="{{ route('lead.capture') }}" method="post">
        @csrf
        <div class="form-row">
            <label>Nombre<input name="first_name" value="{{ old('first_name') }}" required placeholder="Tu nombre"></label>
            <label>Apellidos<input name="last_name" value="{{ old('last_name') }}" placeholder="Tus apellidos"></label>
        </div>
        <div class="form-row">
            <label>Teléfono<input name="phone" value="{{ old('phone') }}" required placeholder="+51 999 999 999"></label>
            <label>Correo<input type="email" name="email" value="{{ old('email') }}" placeholder="nombre@correo.com"></label>
        </div>
        <label>¿Qué necesitas?
            <select name="interest"><option>Quiero comprar</option><option>Quiero vender</option><option>Busco alquiler</option><option>Quiero invertir</option></select>
        </label>
        <label>Cuéntame un poco más<textarea name="notes" rows="3" placeholder="Zona, presupuesto, tiempos...">{{ old('notes') }}</textarea></label>
        @if($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
        <button class="button button-accent" type="submit">Solicitar asesoría <span>→</span></button>
        <small>Al enviar aceptas ser contactado para atender tu solicitud.</small>
    </form>
</section>
