@php
    $pageTitle = trim($__env->yieldContent('title'))
        ?: (($settings['seo_title'] ?? null) ?: 'Carmen Mestanza · Asesora inmobiliaria en Lima');
    $pageDescription = trim($__env->yieldContent('description'))
        ?: (($settings['seo_description'] ?? null) ?: 'Asesoría inmobiliaria personalizada para comprar, vender o alquilar propiedades en Lima.');
    $pageImageSource = trim($__env->yieldContent('image'))
        ?: (($settings['seo_image_path'] ?? null) ?: '/og-blue-red.png');
    $imageIsAbsolute = \Illuminate\Support\Str::startsWith($pageImageSource, ['http://', 'https://']);
    $pageImagePath = '/'.ltrim((string) (parse_url($pageImageSource, PHP_URL_PATH) ?: $pageImageSource), '/');
    $knownImageHosts = array_filter([
        parse_url((string) config('app.url'), PHP_URL_HOST), request()->getHost(),
    ]);
    $imageIsLocal = ! $imageIsAbsolute
        || in_array(parse_url($pageImageSource, PHP_URL_HOST), $knownImageHosts, true);
    $localImagePath = str_starts_with($pageImagePath, '/storage/')
        ? storage_path('app/public/'.substr($pageImagePath, 9))
        : public_path(ltrim($pageImagePath, '/'));
    $localImagePath = $imageIsLocal && is_file($localImagePath) ? $localImagePath : null;
    $pageImage = $localImagePath ? url($pageImagePath)
        : ($imageIsAbsolute ? $pageImageSource : url($pageImagePath));
    $pageImageInfo = $localImagePath ? (@getimagesize($localImagePath) ?: []) : [];
    $pageImageAlt = trim($__env->yieldContent('image_alt')) ?: $pageTitle;
    $pageImageType = trim($__env->yieldContent('image_type')) ?: ($pageImageInfo['mime'] ?? null);
    $pageImageWidth = trim($__env->yieldContent('image_width')) ?: ($pageImageInfo[0] ?? null);
    $pageImageHeight = trim($__env->yieldContent('image_height')) ?: ($pageImageInfo[1] ?? null);
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $robots = trim($__env->yieldContent('robots')) ?: 'index,follow,max-image-preview:large';
    $logoUrl = url('/images/carmen-mestanza-logo.webp');
    $businessSchema = [
        '@context' => 'https://schema.org', '@type' => 'RealEstateAgent',
        '@id' => url('/').'#business', 'name' => 'Carmen Mestanza',
        'url' => url('/'), 'logo' => $logoUrl, 'image' => $pageImage,
        'telephone' => $settings['phone'] ?? null,
        'email' => $settings['email'] ?? null,
        'description' => $pageDescription,
        'areaServed' => $settings['service_area'] ?? 'Lima, Perú',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Lima', 'addressCountry' => 'PE'],
    ];
@endphp
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <meta name="theme-color" content="#071b35">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="es-PE" href="{{ $canonical }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="Carmen Mestanza Inmobiliaria">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $pageImage }}">
    @if(str_starts_with($pageImage, 'https://'))<meta property="og:image:secure_url" content="{{ $pageImage }}">@endif
    @if($pageImageType)<meta property="og:image:type" content="{{ $pageImageType }}">@endif
    @if($pageImageWidth)<meta property="og:image:width" content="{{ $pageImageWidth }}">@endif
    @if($pageImageHeight)<meta property="og:image:height" content="{{ $pageImageHeight }}">@endif
    <meta property="og:image:alt" content="{{ $pageImageAlt }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <meta name="twitter:image:alt" content="{{ $pageImageAlt }}">
    <script type="application/ld+json">{!! json_encode($businessSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @stack('structured-data')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-body">
    <header class="site-header" data-header>
        <a class="brand" href="{{ route('home') }}" aria-label="Carmen Mestanza, inicio">
            <span class="brand-logo-frame"><img src="{{ $logoUrl }}" alt="" width="46" height="47"></span>
            <span><strong>Carmen Mestanza</strong><small>Asesoría inmobiliaria</small></span>
        </a>
        <button class="menu-toggle" data-menu-toggle aria-label="Abrir menú" aria-expanded="false">Menú</button>
        <nav class="main-nav" data-menu aria-label="Navegación principal">
            <a href="{{ route('home') }}#servicios">Servicios</a>
            <a href="{{ route('properties.index') }}">Propiedades</a>
            <a href="{{ route('home') }}#carmen">Conóceme</a>
            <a href="{{ route('home') }}#contacto">Contacto</a>
            <a class="button button-dark" href="https://wa.me/{{ $settings['whatsapp'] ?? '51987654321' }}">Hablemos</a>
        </nav>
    </header>

    @if(session('success'))
        <div class="flash flash-success" role="status">{{ session('success') }}</div>
    @endif
    @yield('content')

    <footer class="site-footer">
        <div class="footer-brand">
            <span class="brand-logo-frame brand-logo-frame-light"><img src="{{ $logoUrl }}" alt="" width="46" height="47"></span>
            <div><strong>Carmen Mestanza</strong><p>Decisiones inmobiliarias con claridad.</p></div>
        </div>
        <div><span class="footer-label">Atención</span><p>{{ $settings['service_area'] ?? 'Lima, Perú' }}</p><p>{{ $settings['phone'] ?? '+51 987 654 321' }}</p></div>
        <div><span class="footer-label">Contacto</span><p>{{ $settings['email'] ?? 'carmen@example.com' }}</p><a href="{{ route('login') }}">Acceso CRM</a></div>
        <p class="footer-copy">© {{ date('Y') }} Carmen Mestanza. Todos los derechos reservados.
            <small>Gráficos emoji por <a href="https://github.com/jdecked/twemoji" rel="noopener" target="_blank">Twemoji</a>.</small>
        </p>
    </footer>
    <a class="whatsapp-float" href="https://wa.me/{{ $settings['whatsapp'] ?? '51987654321' }}"
        aria-label="Conversar por WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
</body>
</html>
