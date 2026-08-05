@extends('layouts.auth')

@section('title', 'Recuperar contraseña')

@section('form')
<form class="login-form" method="post" action="{{ route('password.email') }}">
    @csrf
    <span class="login-kicker">Recuperar acceso</span>
    <h2>Recibe un enlace seguro</h2>
    <p>Escribe el correo principal de tu cuenta. El enlace vencerá en 60 minutos.</p>
    @if(session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    <label>Correo electrónico
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
    </label>
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
    <button class="button button-accent" type="submit">Enviar enlace de recuperación</button>
    <a class="login-back" href="{{ route('login') }}">← Volver al acceso</a>
</form>
@endsection
