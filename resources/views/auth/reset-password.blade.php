@extends('layouts.auth')

@section('title', 'Nueva contraseña')

@section('form')
<form class="login-form" method="post" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <span class="login-kicker">Restablecer acceso</span>
    <h2>Crea una nueva contraseña</h2>
    <p>Debe tener 10 caracteres, mayúscula, minúscula, número y símbolo.</p>
    <label>Correo electrónico
        <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
    </label>
    <label>Nueva contraseña
        <input type="password" name="password" required autofocus autocomplete="new-password">
    </label>
    <label>Repetir contraseña
        <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
    <button class="button button-accent" type="submit">Guardar y volver al acceso</button>
</form>
@endsection
