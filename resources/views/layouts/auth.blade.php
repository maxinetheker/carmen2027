<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title') · Carmen Mestanza</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <section class="login-visual">
        <a class="brand brand-light" href="{{ route('home') }}">
            <span class="brand-logo-frame brand-logo-frame-light"><img src="{{ asset('images/carmen-mestanza-logo.webp') }}" alt="" width="46" height="47"></span>
            <span><strong>Carmen Mestanza</strong><small>Asesoría inmobiliaria</small></span>
        </a>
        <div><span class="eyebrow eyebrow-light">Tu negocio, en un solo lugar</span>
            <h1>Relaciones que crecen.<br>Decisiones que avanzan.</h1>
            <p>Inventario, clientes, oportunidades y agenda siempre conectados.</p></div>
        <small>CRM diseñado para Carmen Mestanza</small>
    </section>
    <section class="login-panel">@yield('form')</section>
</body>
</html>
