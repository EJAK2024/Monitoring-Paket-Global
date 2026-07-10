<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supply Chain Risk Intelligence')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #1a2035; }
        .sidebar .nav-link { color: #a0aec0; padding: 0.7rem 1.2rem; border-radius: 0.4rem; margin: 0.1rem 0; transition: 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar .nav-link i { width: 1.5rem; }
        .sidebar .brand { padding: 1.2rem; font-weight: 700; font-size: 1.1rem; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .content { padding: 1.5rem; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-header { background: #fff; border-bottom: 1px solid #e9ecef; font-weight: 600; border-radius: 0.75rem 0.75rem 0 0 !important; }
        .stat-card { padding: 1.2rem; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 700; }
        .stat-card .stat-label { color: #6c757d; font-size: 0.85rem; }
        .map-container { height: 450px; border-radius: 0.75rem; }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar" style="width: 250px; flex-shrink: 0;">
            <div class="brand">Monitoring Paket Internasional</div>
            <nav class="nav flex-column px-2 py-3">
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
