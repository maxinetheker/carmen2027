@extends('layouts.admin')

@section('title', 'Mi cuenta')
@section('heading', 'Mi cuenta')
@section('eyebrow', 'Seguridad y acceso')

@section('content')
<div class="account-grid">
    <form class="form-card" method="post" action="{{ route('admin.account.email') }}">
        @csrf @method('put')
        <div class="form-card-heading">
            <div><h2>Correo principal</h2><p>Aquí recibirás recuperaciones y avisos de acceso.</p></div>
        </div>
        <label class="field"><span>Correo electrónico</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
        </label>
        <label class="field"><span>Contraseña actual</span>
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        @if($errors->emailUpdate->any())<div class="form-error">{{ $errors->emailUpdate->first() }}</div>@endif
        <button class="button button-accent" type="submit">Actualizar correo</button>
    </form>

    <form class="form-card" method="post" action="{{ route('admin.account.password') }}">
        @csrf @method('put')
        <div class="form-card-heading">
            <div><h2>Cambiar contraseña</h2><p>Las demás sesiones se cerrarán por seguridad.</p></div>
        </div>
        <p class="account-note">Usa al menos 10 caracteres con mayúscula, minúscula, número y símbolo.</p>
        <label class="field"><span>Contraseña actual</span>
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label class="field"><span>Nueva contraseña</span>
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        <label class="field"><span>Repetir nueva contraseña</span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>
        @if($errors->passwordUpdate->any())<div class="form-error">{{ $errors->passwordUpdate->first() }}</div>@endif
        <button class="button button-accent" type="submit">Guardar nueva contraseña</button>
    </form>
</div>
@endsection
