@extends('layouts.auth')

@section('title', 'Acceso CRM')

@section('form')
<form class="login-form" method="post" action="{{ route('login.store') }}">
    @csrf
    <span class="login-kicker">Panel administrativo</span>
    <h2>Bienvenida, Carmen</h2>
    <p>Ingresa para continuar con tu jornada.</p>
    @if(session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    <label>Correo electrónico
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="carmen@correo.com">
    </label>
    <label>Contraseña
        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
    </label>
    <div class="login-options">
        <label class="remember"><input type="checkbox" name="remember"> Mantener mi sesión</label>
        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
    </div>
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
    <button class="button button-accent" type="submit">Ingresar al CRM <span>→</span></button>
    <a class="login-back" href="{{ route('home') }}">← Volver al sitio web</a>
</form>
@endsection
