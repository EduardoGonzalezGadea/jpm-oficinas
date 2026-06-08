<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="login-url" content="{{ route('login') }}">
    @stack('meta')
    <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/jpm.png') }}">
    <title>@yield('title', 'Tesorería | Oficinas')</title>
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
    @yield('content')
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
