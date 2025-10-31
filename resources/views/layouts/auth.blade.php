<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'TMO云迁移')</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Styles -->
    <link href="{{ asset('assets/css/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vendor/fontawesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/modules/auth.css') }}" rel="stylesheet">
    @yield('styles')
    @stack('styles')
</head>
<body style="margin: 0; padding: 0; overflow: hidden;">
    @yield('content')

    <!-- Scripts -->
    <script src="{{ asset('assets/js/vendor/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/toastr.min.js') }}"></script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>
