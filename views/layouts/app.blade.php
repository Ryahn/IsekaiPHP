<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = 'IsekaiPHP';
        if (isset($settingsService) && is_object($settingsService) && method_exists($settingsService, 'get')) {
            $pageTitle = $settingsService->get('app.name') ?: $settingsService->get('app_name') ?: 'IsekaiPHP';
        }
    @endphp
    <title>@yield('title', $pageTitle)</title>
    
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Vite Assets -->
    {!! vite() !!}
    
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                @php
                    $appName = 'IsekaiPHP';
                    if (isset($settingsService) && is_object($settingsService) && method_exists($settingsService, 'get')) {
                        $appName = $settingsService->get('app.name') ?: $settingsService->get('app_name') ?: 'IsekaiPHP';
                    }
                @endphp
                {{ $appName }}
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    @if(auth())
                        <li class="nav-item">
                            <a class="nav-link" href="/admin">Admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout">Logout</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="/login">Login</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <script>
        @php
            $siteUrl = 'http://localhost';
            if (isset($settingsService) && is_object($settingsService) && method_exists($settingsService, 'get')) {
                $siteUrl = $settingsService->get('app.url') ?: $settingsService->get('app_url') ?: env('APP_URL', 'http://localhost');
            } else {
                $siteUrl = env('APP_URL', 'http://localhost');
            }
        @endphp
        window.siteUrl = "{{ $siteUrl }}";
    </script>
    
    @stack('scripts')
</body>
</html>
