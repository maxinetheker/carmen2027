<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM') · Carmen Mestanza</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <aside class="admin-sidebar" data-sidebar>
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span class="brand-logo-frame brand-logo-frame-light"><img src="{{ asset('images/carmen-mestanza-logo.webp') }}" alt="" width="46" height="47"></span>
            <span><strong>Carmen Mestanza</strong><small>CRM inmobiliario</small></span>
        </a>
        <nav class="admin-nav">
            <span class="nav-label">Resumen</span>
            <a class="@if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}"><i>⌂</i> Panel general</a>
            <a class="@if(request()->routeIs('admin.reports')) active @endif" href="{{ route('admin.reports') }}"><i>↗</i> Reportes</a>
            <span class="nav-label">Relaciones</span>
            <a class="@if(request()->routeIs('admin.leads.*')) active @endif" href="{{ route('admin.leads.index') }}"><i>◎</i> Prospectos</a>
            <a class="@if(request()->routeIs('admin.contacts.*')) active @endif" href="{{ route('admin.contacts.index') }}"><i>◉</i> Contactos</a>
            <a class="@if(request()->routeIs('admin.deals.*')) active @endif" href="{{ route('admin.deals.index') }}"><i>◇</i> Oportunidades</a>
            <span class="nav-label">Operación</span>
            <a class="@if(request()->routeIs('admin.properties.*')) active @endif" href="{{ route('admin.properties.index') }}"><i>⌂</i> Propiedades</a>
            <a class="@if(request()->routeIs('admin.tasks.*')) active @endif" href="{{ route('admin.tasks.index') }}"><i>✓</i> Tareas</a>
            <a class="@if(request()->routeIs('admin.appointments.*')) active @endif" href="{{ route('admin.appointments.index') }}"><i>□</i> Agenda</a>
            <a class="@if(request()->routeIs('admin.notifications.*')) active @endif" href="{{ route('admin.notifications.edit') }}"><i>◎</i> Notificaciones</a>
            <span class="nav-label">Presencia digital</span>
            <a class="@if(request()->routeIs('admin.settings.*')) active @endif" href="{{ route('admin.settings.edit') }}"><i>✦</i> Editar sitio web</a>
        </nav>
        <div class="sidebar-profile">
            <span class="profile-avatar profile-logo"><img src="{{ asset('images/carmen-mestanza-logo.webp') }}" alt=""></span>
            <a class="sidebar-account" href="{{ route('admin.account.edit') }}"><strong>{{ auth()->user()->name }}</strong><small>Mi cuenta</small></a>
            <form method="post" action="{{ route('logout') }}">@csrf<button aria-label="Cerrar sesión">↗</button></form>
        </div>
    </aside>
    <div class="admin-shell">
        <header class="admin-topbar">
            <button class="sidebar-toggle" data-sidebar-toggle aria-label="Abrir navegación">☰</button>
            <div><span>@yield('eyebrow', 'CRM inmobiliario')</span><h1>@yield('heading', 'Panel general')</h1></div>
            <div class="topbar-actions">
                <a href="{{ route('home') }}" target="_blank">Ver sitio ↗</a>
                <span class="online-dot">En línea</span>
            </div>
        </header>
        @if(session('success'))<div class="flash flash-admin" role="status">{{ session('success') }}</div>@endif
        <main class="admin-content">@yield('content')</main>
    </div>
</body>
</html>
