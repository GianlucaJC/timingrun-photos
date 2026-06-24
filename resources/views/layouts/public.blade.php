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
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('public.events.index') }}" class="site-title-link">
                <i class="bi bi-stopwatch site-title-icon"></i>
                <span class="site-title">Timing<span class="site-title-highlight">RUN</span> <span class="site-title-secondary">Photos</span></span>
            </a>
            
            <a href="{{ route('public.cart.index') }}" class="btn btn-outline-light d-flex align-items-center gap-2 position-relative px-3 py-1 text-nowrap rounded-pill border-opacity-25" style="border-color: rgba(255,255,255,0.25);">
                <i class="bi bi-cart3" style="color: var(--timingrun-orange);"></i>
                <span class="d-none d-sm-inline">Carrello</span>
                <span id="header-cart-badge" class="badge bg-danger rounded-pill {{ (session('cart') && count(session('cart')) > 0) ? '' : 'd-none' }}" style="font-size: 0.75rem; padding: 0.25em 0.6em;">
                    {{ session('cart') ? count(session('cart')) : 0 }}
                </span>
            </a>
        </div>
    </header>

    <div class="container main-content">
        <main>
            @if(session('success') && !request()->routeIs('public.cart.index'))
                <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert" style="background-color: var(--timingrun-green); color: #111; font-weight: 500;">
                    <strong><i class="bi bi-check-circle-fill"></i></strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error') && !request()->routeIs('public.cart.index'))
                <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert" style="background-color: #ff3333; color: #fff; font-weight: 500;">
                    <strong><i class="bi bi-exclamation-triangle-fill"></i></strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

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