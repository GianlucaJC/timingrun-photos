<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Area Fotografi - TimingRun Photos')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Public CSS (per il tema scuro) -->
    <link href="{{ asset('css/public.css') }}" rel="stylesheet">

    <!-- Axios CDN for AJAX requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    </script>
    {{-- Vite non è usato in questo layout per coerenza con gli altri (public, login) che usano CDN --}}
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>
<body>
    <!-- Div per l'overlay di foto sullo sfondo, per coerenza grafica -->
    <div class="background-photo-overlay"></div>

    <header class="site-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('photographer.events.index') }}" class="site-title-link">
                <i class="bi bi-camera-fill site-title-icon"></i>
                <div>
                    <span class="site-title">TimingRun<span class="site-title-highlight">Photos</span></span>
                    <span class="d-block site-title-secondary">Area Fotografi</span>
                </div>
            </a>

            @auth
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-fill"></i> {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
            @endauth
        </div>
    </header>

    <div class="container main-content">
        <main>
            @yield('content')
        </main>
    </div>

    <footer class="site-footer">
        <div class="container text-center">
            <p class="mb-0">&copy; {{ date('Y') }} TimingRun Photos. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>