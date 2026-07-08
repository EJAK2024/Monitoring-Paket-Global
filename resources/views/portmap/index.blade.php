@extends('layouts.app')

@section('title', 'Vessel Monitoring — Port Intelligence')

@section('content')
<style>
    .content { padding: 0 !important; }
    #sidebar { width: 380px; will-change: transform; border-right: 1px solid rgba(0,0,0,0.06); }
    @media (max-width: 768px) { #sidebar { width: 100%; } }
    .vessel-item:hover, .port-item:hover { background: #f0f4ff; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .status-dot.active { background: #198754; }
    .status-dot.inactive { background: #dc3545; }
    .status-dot.unknown { background: #6c757d; }
    .ship-panel-card { background: rgba(255,255,255,0.97); backdrop-filter: blur(8px); }
    .sidebar-collapsed #sidebar { transform: translateX(-400px); }
    #sidebarOpenBtn { display: none; transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 3px 14px rgba(0,0,0,0.18); }
    .sidebar-collapsed #sidebarOpenBtn { display: flex; }
    #sidebarOpenBtn:hover { transform: scale(1.08); box-shadow: 0 5px 20px rgba(0,0,0,0.25); }
    #sidebarToggleBtn { transition: all 0.25s ease; }
    #sidebarToggleBtn:hover { background: rgba(255,255,255,0.25) !important; }
    #sidebarToggleBtn .bi { transition: transform 0.35s ease; }
    .sidebar-header { background: linear-gradient(135deg, #1a2035 0%, #2a3050 100%); color: #fff; }
    #sidebarTabs { border-bottom: 1px solid rgba(0,0,0,0.05); }
    #sidebarTabs .nav-link { border: none !important; color: #8892a0; padding: 0.5rem 0.8rem; font-size: 0.75rem; transition: all 0.2s; position: relative; }
    #sidebarTabs .nav-link:hover { color: #0d6efd; background: transparent; }
    #sidebarTabs .nav-link.active { color: #0d6efd; background: transparent; }
    #sidebarTabs .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 60%; height: 2px; background: #0d6efd; border-radius: 1px; }
    .panel-section-title { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
    .sidebar-list { max-height: 320px; overflow-y: auto; }
    .sidebar-list::-webkit-scrollbar { width: 4px; }
    .sidebar-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
</style> 
<div class="position-relative" style="height: 100vh;">
    <div id="portMap" style="height: 100%; width: 100%; border-radius: 0;"></div>

    <div id="sidebar" class="position-absolute top-0 start-0 shadow" style="z-index: 1020; height: 100vh; background: rgba(255,255,255,0.97); backdrop-filter: blur(8px); transition: transform 0.3s ease; display: flex; flex-direction: column;">
        <div class="sidebar-header d-flex justify-content-between align-items-center px-3" style="min-height: 52px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-grid-3x3-gap-fill text-primary" style="font-size:1.1rem;"></i>
                <div>
                    <span class="fw-semibold" style="font-size:0.9rem;">Marine Monitor</span>
                    <small class="text-white-50 d-block" id="sidebarStatus" style="font-size:0.65rem;line-height:1;">Loading...</small>
                </div>
                @if (!empty($liveVessels))
                    <span class="badge bg-success" style="font-size:0.5rem;">LIVE</span>
                @elseif ($apiStatus === 'invalid_key')
                    <span class="badge bg-danger" style="font-size:0.5rem;">KEY ERR</span>
                @elseif ($apiStatus === 'no_key')
                    <span class="badge bg-warning text-dark" style="font-size:0.5rem;">NO KEY</span>
                @else
                    <span class="badge bg-secondary" style="font-size:0.5rem;">SIM</span>
                @endif
            </div>
            <button id="sidebarToggleBtn" class="btn btn-sm text-white border-0 p-0" style="width:28px;height:28px;opacity:0.7;" title="Toggle sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>

        <ul class="nav nav-tabs nav-fill" id="sidebarTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-search" data-bs-toggle="tab" data-bs-target="#panel-search" type="button" role="tab"><i class="bi bi-search"></i> Search</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ports" data-bs-toggle="tab" data-bs-target="#panel-ports" type="button" role="tab"><i class="bi bi-geo-alt"></i> Ports</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-vessels" data-bs-toggle="tab" data-bs-target="#panel-vessels" type="button" role="tab"><i class="bi bi-ship"></i> Vessels</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-layers" data-bs-toggle="tab" data-bs-target="#panel-layers" type="button" role="tab"><i class="bi bi-layers"></i> Layers</button>
            </li>
        </ul>

        <div class="tab-content flex-grow-1 overflow-auto" style="font-size:0.85rem;">
            <div class="tab-pane fade show active p-2" id="panel-search" role="tabpanel">
                <input type="text" id="portSearch" class="form-control form-control-sm mb-1" placeholder="Search port or country...">
                <select id="portTypeFilter" class="form-select form-select-sm mb-1">
                    <option value="">All port types</option>
                    @foreach ($portTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <div id="searchResults" class="list-group mb-1" style="max-height: 260px; overflow-y: auto; display: none;"></div>
                <div class="d-flex align-items-center justify-content-between small text-muted bg-light rounded px-2 py-1 mt-1">
                    <span><i class="bi bi-geo-alt me-1"></i><span id="portCount">-</span></span>
                    <span><i class="bi bi-signpost-2 me-1"></i><span id="routeCount">-</span></span>
                </div>
            </div>

            <div class="tab-pane fade p-2" id="panel-ports" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="panel-section-title"><i class="bi bi-building me-1"></i>Port Directory</span>
                    <span id="portCount2" class="badge bg-primary rounded-pill">0</span>
                </div>
                <select id="portTypeFilter2" class="form-select form-select-sm mb-1">
                    <option value="">All types</option>
                    @foreach ($portTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <div id="portList" class="sidebar-list">
                    <div class="text-center text-muted py-3" style="font-size:0.8rem;">Loading ports...</div>
                </div>
            </div>

            <div class="tab-pane fade p-2" id="panel-vessels" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="panel-section-title"><i class="bi bi-ship me-1"></i>Active Vessels</span>
                    <span id="shipCount2" class="badge bg-success rounded-pill">0</span>
                </div>
                <div class="d-flex gap-1 mb-1">
                    <select id="vesselTypeFilter" class="form-select form-select-sm flex-grow-1">
                        <option value="">All types</option>
                        <option value="container">Container Ship</option>
                        <option value="tanker">Tanker</option>
                        <option value="bulk">Bulk Carrier</option>
                        <option value="lng">LNG Carrier</option>
                    </select>
                    <button id="refreshVesselsBtn" class="btn btn-sm btn-outline-primary px-2" title="Refresh vessel data">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div id="vesselList" class="sidebar-list">
                    <div class="text-center text-muted py-3" style="font-size:0.8rem;">Initializing vessels...</div>
                </div>
                <div class="small text-muted text-end mt-1" id="vesselUpdateInfo"></div>
            </div>

            <div class="tab-pane fade p-2" id="panel-layers" role="tabpanel">
                <div class="panel-section-title mb-2"><i class="bi bi-layers me-1"></i>Map Overlays</div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="layerRoutes" checked>
                    <label class="form-check-label" for="layerRoutes">Trade routes</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="layerShips" checked>
                    <label class="form-check-label" for="layerShips">
                        @if (!empty($liveVessels))
                            Live AIS vessels
                        @else
                            Simulated vessels
                        @endif
                    </label>
                </div>
                <hr class="my-2">
                <div class="panel-section-title mb-2">Legend</div>
                <div class="row g-1 small">
                    <div class="col-6"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#0d6efd;"></span> Container port</div>
                    <div class="col-6"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#198754;"></span> Energy port</div>
                    <div class="col-6"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#ffc107;"></span> Industrial port</div>
                    <div class="col-6"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#dc3545;"></span> Multi-purpose port</div>
                </div>
                <hr class="my-2">
                <div class="row g-1 small">
                    <div class="col-6"><span style="font-size:12px;color:#0d6efd;">▷</span> Container vessel</div>
                    <div class="col-6"><span style="font-size:12px;color:#dc3545;">◇</span> Tanker</div>
                    <div class="col-6"><span style="font-size:12px;color:#198754;">◁</span> Bulk carrier</div>
                    <div class="col-6"><span style="font-size:12px;color:#0dcaf0;">○</span> LNG carrier</div>
                    <div class="col-12 mt-1"><span style="font-size:12px;">- - -</span> Trade route</div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between px-2 py-1 border-top small text-muted" style="background:#f8f9fc;">
            <a href="{{ route('dashboard') }}" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
            <span id="sidebarFooterInfo">Initializing...</span>
        </div>
    </div>

    <button id="sidebarOpenBtn" class="position-absolute btn btn-primary shadow-sm" style="z-index: 1010; display: none; top: 20px; left: 20px; border-radius: 50%; width: 44px; height: 44px; padding: 0; font-size: 1.3rem; cursor: pointer;" title="Show sidebar">
        <i class="bi bi-arrow-bar-right"></i>
    </button>

    <div id="shipPanel" class="position-absolute bottom-0 start-0 p-3" style="z-index: 1000; display: none;">
        <div class="card shadow ship-panel-card" style="width: 340px;">
            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <span class="fw-semibold small"><i class="bi bi-ship"></i> <span id="shipPanelName">-</span></span>
                <button class="btn btn-sm btn-outline-light py-0 px-1" onclick="closeShipPanel()">&times;</button>
            </div>
            <div class="card-body py-2 px-3" style="font-size:0.85rem;">
                <div class="row mb-1"><div class="col-5 text-muted">Type</div><div class="col-7" id="shipPanelType">-</div></div>
                <div class="row mb-1"><div class="col-5 text-muted">Speed</div><div class="col-7" id="shipPanelSpeed">-</div></div>
                <div class="row mb-1"><div class="col-5 text-muted">Heading</div><div class="col-7" id="shipPanelHeading">-</div></div>
                <div class="row mb-1"><div class="col-5 text-muted">Route</div><div class="col-7" id="shipPanelRoute">-</div></div>
                <div class="row mb-1"><div class="col-5 text-muted">Destination</div><div class="col-7" id="shipPanelDest">-</div></div>
                <div class="row mb-1"><div class="col-5 text-muted">Status</div><div class="col-7" id="shipPanelStatus">-</div></div>
                <div class="mt-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" id="shipFollowBtn" onclick="toggleFollowShip()">
                        <i class="bi bi-crosshair"></i> Follow
                    </button>
                    <button class="btn btn-sm btn-outline-info" id="shipRouteHighlightBtn" onclick="highlightShipRoute()">
                        <i class="bi bi-signpost-2"></i> Route
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.__ROUTES = @json($routes);
window.__HAS_LIVE = @json(!empty($liveVessels));
window.__LIVE_VESSELS = @json($liveVessels);
window.__API_STATUS = {!! json_encode($apiStatus ?? 'unknown') !!};
window.__MAPTILER_KEY = '{{ config('services.maptiler.key') }}';
</script>
@if (count($routes) === 0)
<script>
    (function(){
        var el = document.getElementById('sidebarStatus');
        if (el) el.textContent = 'No routes — seed ports first';
    })();
</script>
@endif
@vite('resources/js/portmap.js')
@endsection
