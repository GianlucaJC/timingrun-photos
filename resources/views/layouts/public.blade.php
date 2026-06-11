<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TimingRun Photos')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Public CSS -->
    <link href="{{ asset('css/public.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
    <!-- Div per l'overlay di foto sullo sfondo -->
    <div class="background-photo-overlay"></div>

    <header class="site-header">
        <div class="container d-flex justify-content-center align-items-center">
            <a href="{{ route('public.events.index') }}" class="site-title-link">
                <i class="bi bi-stopwatch site-title-icon"></i>
                <span class="site-title">Timing<span class="site-title-highlight">RUN</span> <span class="site-title-secondary">Photos</span></span>
            </a>
        </div>
    </header>

    <div class="container main-content">
        <main>
            @yield('content')
        </main>
    </div>

    <footer class="site-footer text-center">
        <p class="mb-0">&copy; {{ date('Y') }} TimingRun Photos. Tutti i diritti riservati.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>