<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title') | {{ config('app.name') }}</title>
    
    <!-- CSS files -->
    <link href="{{asset('css/tabler.min.css?1738096682')}}" rel="stylesheet" />
    <link href="{{asset('css/custom-theme.css')}}?v={{ time() }}" rel="stylesheet" />
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-card">
            <div class="auth-logo-wrap">
                <a href="{{route('login')}}">
                    <img src="{{asset('img/logo.jpg')}}" alt="{{ config('app.name') }}" />
                </a>
            </div>

            @if (session()->has('error'))
                @include('components.alert.danger')
            @endif

            @if (session()->has('warning'))
                @include('components.alert.warning')
            @endif

            @yield('content')

            <div class="text-center mt-4">
                <p class="text-muted small mb-0">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Tabler Core JS -->
    <script src="{{asset('js/tabler.min.js?1738096682')}}" defer></script>
</body>
</html>
