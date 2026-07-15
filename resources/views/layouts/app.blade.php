<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supply Chain Risk Intelligence')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .sidebar { min-height: 100vh; }
        .sidebar .nav-link i { width: 1.5rem; }
        .content { padding: 1.5rem; }
        .stat-card { padding: 1.2rem; }
        .stat-loader { display: none; margin-top: 0.4rem; }
        .stat-loader.active { display: block; }
        .bar-loader {
            height: 15px;
            aspect-ratio: 5;
            background: linear-gradient(90deg, #7c3aed, #06b6d4);
            background-size: 200% 100%;
            -webkit-mask: linear-gradient(90deg, transparent, #000 20% 80%, transparent);
            mask: linear-gradient(90deg, transparent, #000 20% 80%, transparent);
            animation: barLoader 1s infinite linear;
        }
        @keyframes barLoader {
            0% { background-position: 0% 0; }
            100% { background-position: 100% 0; }
        }
        .map-container { height: 450px; border-radius: 0.75rem; }
        .map-loader-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.3s;
        }
        .map-loader-overlay.hidden { display: none; }
        .map-loader {
            width: 45px;
            aspect-ratio: .75;
            --c: #7c3aed;
            background:
                var(--c) 0%   50%,
                var(--c) 50%  50%,
                var(--c) 100% 50%;
            background-repeat: no-repeat;
            animation: mapLoader 1s infinite linear alternate;
        }
        @keyframes mapLoader {
            0%  {background-size: 20% 50% ,20% 50% ,20% 50% }
            20% {background-size: 20% 20% ,20% 50% ,20% 50% }
            40% {background-size: 20% 100%,20% 20% ,20% 50% }
            60% {background-size: 20% 50% ,20% 100%,20% 20% }
            80% {background-size: 20% 50% ,20% 50% ,20% 100%}
            100%{background-size: 20% 50% ,20% 50% ,20% 50% }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar" style="width: 250px; flex-shrink: 0;">
            <div class="brand"><a href="{{ url()->current() }}" class="brand-link">Monitoring Paket Internasional</a></div>
            <nav class="nav flex-column px-2    py-3">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid"></i> Global Country Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('watchlist') ? 'active' : '' }}" href="{{ route('watchlist') }}">
                    <i class="bi bi-star"></i> Favorite Monitoring List
                </a>
                <a class="nav-link {{ request()->routeIs('portmap') ? 'active' : '' }}" href="{{ route('portmap') }}">
                    <i class="bi bi-map"></i> Peta Pelabuhan
                </a>
                <a class="nav-link {{ request()->routeIs('currency') ? 'active' : '' }}" href="{{ route('currency') }}">
                    <i class="bi bi-currency-exchange"></i> Currency Impact Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('viz') ? 'active' : '' }}" href="{{ route('viz') }}">
                    <i class="bi bi-graph-up"></i> Data Visualization
                </a>
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-gear"></i> Admin Dashboard
                </a>
            </nav>
        </div>
        <div class="content flex-grow-1">
            @yield('content')
        </div>
    </div>
    @yield('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((reg) => {
                    console.log('SW registered:', reg.scope);
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'activated') {
                                window.location.reload();
                            }
                        });
                    });
                }).catch((err) => {
                    console.log('SW registration failed:', err);
                });

                let refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (!refreshing) {
                        refreshing = true;
                        window.location.reload();
                    }
                });
            });
        }
    </script>
</body>
</html>
