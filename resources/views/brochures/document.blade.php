<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $documentTitle }}</title>
@include('brochures.partials.base-style', ['theme' => $theme])
</head>
<body>
@foreach($pages as $page)
    @include($page['view'], $page['data'])
@endforeach
</body>
</html>
