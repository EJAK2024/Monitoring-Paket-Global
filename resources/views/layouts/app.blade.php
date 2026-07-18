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
                <a class="nav-link {{ request()->routeIs('supplier.risk') ? 'active' : '' }}" href="{{ route('supplier.risk') }}">
                    <i class="bi bi-truck"></i> Supplier Risk Scoring
                </a>
                <a class="nav-link {{ request()->routeIs('container.tracking') ? 'active' : '' }}" href="{{ route('container.tracking') }}">
                    <i class="bi bi-box-seam"></i> Container Tracking
                </a>
                <a class="nav-link {{ request()->routeIs('alerts') ? 'active' : '' }}" href="{{ route('alerts') }}">
                    <i class="bi bi-bell"></i> Alerts
                    <span id="navAlertBadge" class="badge bg-danger ms-auto" style="display: none;">0</span>
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
        function nav_refreshBadge() {
            fetch('/api/alerts/unread-count')
                .then(r => r.json())
                .then(d => {
                    const badge = document.getElementById('navAlertBadge');
                    if (d.count > 0) {
                        badge.textContent = d.count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(() => {});
        }
        document.addEventListener('DOMContentLoaded', function () {
            nav_refreshBadge();
            setInterval(nav_refreshBadge, 30000);
        });
    </script>
    <div id="toastContainer" style="position:fixed;top:1rem;right:1rem;z-index:9999;"></div>
    <script>
        function showToast(message, type) {
            type = type || 'success';
            var colors = { success: '#198754', danger: '#dc3545', warning: '#ffc107', info: '#0dcaf0' };
            var bg = colors[type] || colors.success;
            var toast = document.createElement('div');
            toast.style.cssText = 'background:' + bg + ';color:#fff;padding:0.75rem 1.25rem;border-radius:0.5rem;margin-bottom:0.5rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:0.9rem;opacity:0;transition:opacity 0.3s;';
            toast.textContent = message;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(function() { toast.style.opacity = '1'; }, 10);
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function() { toast.remove(); }, 300);
            }, 3000);
        }
    </script>
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
