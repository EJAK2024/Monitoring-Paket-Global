@extends('layouts.app')

@section('title', 'Vessel Monitoring — Port Intelligence')

@section('content')
<style>
    .content { padding: 0 !important; }
    #sidebar { width: 380px; will-change: width; border-right: 1px solid rgb(248, 240, 240); transition: width 0.3s ease; color: #fefefe; background: #fff; }
    @media (max-width: 768px) { #sidebar { width: 100%; } }
    .vessel-item:hover, .port-item:hover { background: #0909098e; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .status-dot.active { background: #198754; }
    .status-dot.inactive { background: #dc3545; }
    .status-dot.unknown { background: #e71b1b; }
    .ship-panel-card { background: rgba(99, 96, 96, 0.97); backdrop-filter: blur(8px); }
    .sidebar-collapsed #sidebar { width: 52px !important; min-width: 52px; }
    .sidebar-collapsed #sidebar .sidebar-header > div:first-child > div,
    .sidebar-collapsed #sidebar .sidebar-header > div:first-child > .badge,
    .sidebar-collapsed #sidebar #sidebarTabs,
    .sidebar-collapsed #sidebar .tab-content,
    .sidebar-collapsed #sidebar .border-top { display: none !important; }
    .sidebar-collapsed #sidebar .sidebar-header { justify-content: center; padding: 0.5rem; position: relative; }
    .sidebar-collapsed #sidebar .sidebar-header #sidebarToggleBtn { position: absolute; top: 0.5rem; right: 0.25rem; }
    #sidebarOpenBtn { display: none !important; }
    #sidebarToggleBtn { transition: all 0.25s ease; }
    #sidebarToggleBtn:hover { background: rgba(241, 240, 240, 0.25) !important; }
    #sidebarToggleBtn .bi { transition: transform 0.35s ease; }
    .sidebar-header { background: linear-gradient(135deg, #1a1040 0%, #7559d3 100%); color: #f9f9f9; }
    #sidebarTabs { border-bottom: 1px solid #007ffe; background: #1d3c9b; }
    #sidebarTabs .nav-link { border: none !important; color: #000305; padding: 0.5rem 0.8rem; font-size: 0.75rem; transition: all 0.2s; position: relative; }
    #sidebarTabs .nav-link:hover { color: #0d6efd; background: rgba(13,110,253,0.06); border-radius: 4px 4px 0 0; }
    #sidebarTabs .nav-link.active { color: #0d6efd; background:#0d6efd;; border-radius: 4px 4px 0 0; font-weight: 600; }
    #sidebarTabs .nav-link.active::after { content: ''; position: absolute; bottom: -1px; left: 50%; transform: translateX(-50%); width: 60%; height: 2px; background: #3465ae; border-radius: 1px; }
    .tab-content { background: #05055d; border-top: none; }
    .panel-section-title { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #ffffff; }
    #portSearch, #portTypeFilter { color: #ffffff !important; background: rgba(255,255,255,0.1) !important; border-color: rgba(255,255,255,0.3) !important; }
    #portSearch::placeholder { color: rgba(255,255,255,0.5) !important; }
    #portSearch option, #portTypeFilter option { color: #000000; background: #ffffff; }
    .sidebar-list { max-height: 320px; overflow-y: auto; }
    .sidebar-list::-webkit-scrollbar { width: 4px; }
    .sidebar-list::-webkit-scrollbar-thumb { background: #000000; border-radius: 4px; }
</style> 
<div class="position-relative" style="height: 100vh;">
    <iframe
        id="marineTrafficMap"
        src="https://www.marinetraffic.com/en/ais/embed/zoom:3/centery:20/centerx:30/maptype:0/shownames:false/showmenu:false/remember:false"
        style="height: 100%; width: 100%; border: 0;"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
        title="Marine Traffic Live Map">
    </iframe>

    <div id="sidebar" class="position-absolute top-0 start-0 shadow" style="z-index: 1020; height: 100vh; background: rgba(255,255,255,0.97); backdrop-filter: blur(8px); display: flex; flex-direction: column;">
        <div class="sidebar-header d-flex justify-content-between align-items-center px-3" style="min-height: 52px;">
            <div class="d-flex align-items-center gap-2">
                <i class="" style="font-size:1.1rem;"></i>
                <div>
                    <span class="fw-semibold" style="font-size:0.9rem;">Marine Monitor</span>
                    <small class="text-white-50 d-block" id="sidebarStatus" style="font-size:0.65rem;line-height:1;">Loading...</small>
                </div>
                @if (!empty($liveVessels))
                    <span class="badge bg-success" style="font-size:0.5rem;">LIVE</span>
                @elseif ($apiStatus === 'simulated')
                    <span class="badge bg-info text-dark" style="font-size:0.5rem;">EMBED</span>
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

        <div class="tab-content flex-grow-2 overflow-auto" style="font-size:0.85rem;">
            <div class="tab-pane fade show active p-2" id="panel-search" role="tabpanel">
                <div class="panel-section-title mb-1"><i class="bi bi-search me-1"></i>Port Search</div>
                <input type="text" id="portSearch" class="form-control form-control-sm mb-2" placeholder="Search port or country...">
                <select id="portTypeFilter" class="form-select form-select-sm mb-1">
                    <option value="">All port types</option>
                    @foreach ($portTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <div id="searchResults" class="list-group mb-1" style="max-height: 200px; overflow-y: auto; display: none;"></div>
                <div class="d-flex align-items-center justify-content-between small text-muted bg-light rounded px-2 py-1 mt-1">
                    <span><i class="bi bi-geo-alt me-1"></i><span id="portCount">-</span></span>
                    <span><i class="bi bi-signpost-2 me-1"></i><span id="routeCount">-</span></span>
                </div>
                <hr class="my-2">
                <div class="panel-section-title mb-1"><i class="bi bi-ship me-1"></i>Vessel Search</div>
                <div class="input-group input-group-sm mb-1">
                    <input type="text" id="vesselSearchInput" class="form-control" placeholder="Search by name, MMSI, or IMO...">
                    <button id="vesselSearchBtn" class="btn btn-outline-primary" type="button"><i class="bi bi-search"></i></button>
                </div>
                <div id="vesselSearchResults" class="list-group mb-1" style="max-height: 280px; overflow-y: auto; display: none;"></div>
                <div id="vesselSearchStatus" class="small text-muted mt-1" style="display:none;"></div>
            </div>

            <div class="tab-pane fade p-2" id="panel-ports" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="panel-section-title"><i class="bi bi-building me-1"></i>Port Directory</span>
                    <span id="portCount2" class="badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;">0</span>
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
                    <span id="shipCount2" class="badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;">0</span>
                </div>
                <div class="d-flex gap-1 mb-1">
                    <select id="vesselTypeFilter" class="form-select form-select-sm flex-grow-1">
                        <option value="">All types</option>
                        <option value="container">Container Ship</option>
                        <option value="tanker">Tanker</option>
                        <option value="bulk">Bulk Carrier</option>
                        <option value="lng">LNG Carrier</option>
                        <option value="tracked">Tracked Only</option>
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
                    <div class="col-6"><span style="font-size:12px;color:#0d6efd;">&#9655;</span> Container vessel</div>
                    <div class="col-6"><span style="font-size:12px;color:#dc3545;">&#9671;</span> Tanker</div>
                    <div class="col-6"><span style="font-size:12px;color:#198754;">&#9665;</span> Bulk carrier</div>
                    <div class="col-6"><span style="font-size:12px;color:#0dcaf0;">&#9675;</span> LNG carrier</div>
                    <div class="col-12 mt-1"><span style="font-size:12px;">- - -</span> Trade route</div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end px-2 py-1 border-top small" style="background:#f8f9fc;">
            <a id="openMTBtn" href="https://www.marinetraffic.com/en/ais/embed/zoom:3/centery:20/centerx:30/maptype:0/shownames:false/showmenu:false/remember:false" target="_blank" rel="noopener"
               class="text-decoration-none d-flex align-items-center gap-1" style="font-size:0.72rem;color:#6c757d;" title="Buka di MarineTraffic">
                <i class="bi bi-box-arrow-up-right"></i> MarineTraffic
            </a>
        </div>
    </div>

    <button id="sidebarOpenBtn" class="position-absolute btn btn-primary shadow-sm" style="z-index: 1010; display: none; top: 20px; left: 20px; border-radius: 50%; width: 44px; height: 44px; padding: 0; font-size: 1.3rem; cursor: pointer;" title="Show sidebar">
        <i class="bi bi-arrow-bar-right"></i>
    </button>

    <div id="locationInfoPanel" class="position-absolute bottom-0 end-0 p-3" style="z-index: 1030; display: none;">
        <div class="card shadow ship-panel-card" style="width: 280px;">
            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <span class="fw-semibold small"><i class="bi bi-geo-alt"></i> <span id="locationInfoName">-</span></span>
                <button class="btn btn-sm btn-outline-light py-0 px-1" onclick="closeLocationInfo()">&times;</button>
            </div>
            <div class="card-body py-2 px-3" style="font-size:0.85rem;">
                <div class="row mb-1"><div class="col-5 text-muted">Koordinat</div><div class="col-7" id="locationInfoCoords">-</div></div>
                <div id="locationInfoExtra"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('sidebarStatus').textContent = 'Marine Traffic Embed';

function showLocationInfo(name, lat, lng, extra) {
    var panel = document.getElementById('locationInfoPanel');
    if (!panel) return;
    var dir = lat >= 0 ? 'N' : 'S';
    var dirLng = lng >= 0 ? 'E' : 'W';
    document.getElementById('locationInfoName').textContent = name;
    document.getElementById('locationInfoCoords').textContent = Math.abs(lat).toFixed(4) + '\u00b0' + dir + ', ' + Math.abs(lng).toFixed(4) + '\u00b0' + dirLng;
    document.getElementById('locationInfoExtra').innerHTML = extra || '';
    panel.style.display = 'block';
    updateMapCenter(lat, lng);
}

function updateMapCenter(lat, lng) {
    var iframe = document.getElementById('marineTrafficMap');
    if (iframe) {
        iframe.src = 'https://www.marinetraffic.com/en/ais/embed/zoom:10/centery:' + lat + '/centerx:' + lng + '/maptype:0/shownames:true/showmenu:false/remember:false';
    }
}

function closeLocationInfo() {
    var panel = document.getElementById('locationInfoPanel');
    if (panel) panel.style.display = 'none';
}

var sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
var sidebarHidden = false;
sidebarToggleBtn.addEventListener('click', function () {
    sidebarHidden = !sidebarHidden;
    document.body.classList.toggle('sidebar-collapsed', sidebarHidden);
    sidebarToggleBtn.querySelector('i').className = sidebarHidden ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
});

document.getElementById('portTypeFilter2').addEventListener('change', function () { loadPortList(this.value); });

var allPorts = [];

function renderPortItem(p) {
    return '<div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom port-item" ' +
        'style="cursor:pointer;" data-lat="' + p.latitude + '" data-lng="' + p.longitude + '" data-name="' + p.name + '">' +
        '<div><div class="fw-semibold" style="font-size:0.85rem;">' + p.name + '</div>' +
        '<small class="text-muted">' + p.country + '</small></div>' +
        '<span class="badge bg-primary rounded-pill" style="font-size:0.7rem;">' + (p.port_type||'N/A') + '</span></div>';
}

function loadPortList(type) {
    var filtered = type ? allPorts.filter(function(p){ return p.port_type === type; }) : allPorts;
    document.getElementById('portCount2').textContent = filtered.length;
    document.getElementById('portList').innerHTML = filtered.map(renderPortItem).join('');
}

function showSearchResults(results) {
    var el = document.getElementById('searchResults');
    if (!results.length) { el.style.display = 'none'; return; }
    el.style.display = 'block';
    el.innerHTML = results.map(function(p){
        return '<button type="button" class="list-group-item list-group-item-action py-1" data-lat="' + p.latitude + '" data-lng="' + p.longitude + '" data-name="' + p.name + '">' +
            '<div class="fw-semibold" style="font-size:0.8rem;">' + p.name + '</div>' +
            '<small class="text-muted">' + p.country + ' &middot; ' + (p.port_type||'N/A') + '</small></button>';
    }).join('');
}

document.getElementById('portList').addEventListener('click', function(e) {
    var item = e.target.closest('.port-item');
    if (!item) return;
    var lat = parseFloat(item.dataset.lat);
    var lng = parseFloat(item.dataset.lng);
    if (!isNaN(lat) && !isNaN(lng)) showLocationInfo(item.dataset.name, lat, lng, '<small class="text-muted">Port</small>');
});

document.getElementById('searchResults').addEventListener('click', function(e) {
    var btn = e.target.closest('[data-lat]');
    if (!btn) return;
    var lat = parseFloat(btn.dataset.lat);
    var lng = parseFloat(btn.dataset.lng);
    if (!isNaN(lat) && !isNaN(lng)) showLocationInfo(btn.dataset.name, lat, lng, '<small class="text-muted">Port</small>');
});

var searchTimeout = null;
document.getElementById('portSearch').addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    clearTimeout(searchTimeout);
    if (q.length < 2) { document.getElementById('searchResults').style.display = 'none'; return; }
    searchTimeout = setTimeout(function() {
        var typeFilter = document.getElementById('portTypeFilter').value;
        var results = allPorts.filter(function(p) {
            var matchName = p.name.toLowerCase().indexOf(q) !== -1;
            var matchCountry = p.country.toLowerCase().indexOf(q) !== -1;
            var matchType = !typeFilter || p.port_type === typeFilter;
            return (matchName || matchCountry) && matchType;
        });
        showSearchResults(results.slice(0, 15));
    }, 200);
});

fetch('/api/portmap/ports').then(function(r){return r.json();}).then(function(ports){
    allPorts = ports;
    document.getElementById('portCount').textContent = ports.length + ' ports';
    document.getElementById('routeCount').textContent = '-';
    loadPortList('');
});

document.getElementById('vesselUpdateInfo').textContent = 'Loading vessels...';
document.getElementById('shipCount2').textContent = '-';

function renderVesselItem(v) {
    var typeColors = { container: '#0d6efd', tanker: '#dc3545', bulk: '#198754', lng: '#0dcaf0', passenger: '#6f42c1', cargo: '#fd7e14', other: '#6c757d' };
    var color = typeColors[v.vessel_type] || '#6c757d';
    var hasPos = v.latitude && v.longitude;
    var typeLabels = { container: 'Container', tanker: 'Tanker', bulk: 'Bulk', lng: 'LNG', passenger: 'Passenger', cargo: 'Cargo', other: 'Other' };
    var typeLabel = typeLabels[v.vessel_type] || v.vessel_type;

    return '<div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom vessel-item" ' +
        'style="cursor:pointer;border-left:3px solid ' + color + ';" ' +
        'data-lat="' + (v.latitude || '') + '" data-lng="' + (v.longitude || '') + '" data-name="' + (v.name || '').replace(/"/g, '&quot;') + '">' +
        '<div style="flex:1;min-width:0;">' +
        '<div class="fw-semibold text-truncate" style="font-size:0.82rem;">' + v.name + '</div>' +
        '<small class="text-muted" style="font-size:0.7rem;">' + (v.flag_country || '') + ' &middot; ' + typeLabel + '</small>' +
        '</div>' +
        '<div class="d-flex gap-1">' +
        (hasPos ? '<button class="btn btn-sm btn-outline-primary py-0 px-1 vessel-goto-btn" style="font-size:0.7rem;" data-lat="' + v.latitude + '" data-lng="' + v.longitude + '" data-name="' + (v.name || '').replace(/"/g, '&quot;') + '" title="Show on map"><i class="bi bi-geo-alt"></i></button>' : '') +
        '</div>' +
        '</div>';
}

function loadVesselList(filter) {
    fetch('/api/portmap/vessels').then(function(r){return r.json();}).then(function(data){
        var vessels = data.live || data;
        var filtered = vessels;
        if (filter) {
            filtered = vessels.filter(function(v){ return v.vessel_type === filter; });
        }
        document.getElementById('shipCount2').textContent = filtered.length;
        document.getElementById('vesselList').innerHTML = filtered.map(renderVesselItem).join('');
        document.getElementById('vesselUpdateInfo').textContent = filtered.length + ' vessels loaded';
    }).catch(function(){
        document.getElementById('vesselList').innerHTML = '<div class="text-center text-muted py-3" style="font-size:0.8rem;">Failed to load vessels</div>';
    });
}

document.getElementById('vesselTypeFilter').addEventListener('change', function() {
    loadVesselList(this.value);
});

document.getElementById('refreshVesselsBtn').addEventListener('click', function() {
    loadVesselList(document.getElementById('vesselTypeFilter').value);
});

document.getElementById('vesselList').addEventListener('click', function(e) {
    var gotoBtn = e.target.closest('.vessel-goto-btn');
    if (gotoBtn) {
        e.stopPropagation();
        var lat = parseFloat(gotoBtn.dataset.lat);
        var lng = parseFloat(gotoBtn.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) showLocationInfo(gotoBtn.dataset.name, lat, lng, '<small class="text-muted">Vessel</small>');
        return;
    }
    var item = e.target.closest('.vessel-item');
    if (item && item.dataset.lat && item.dataset.lng) {
        var lat = parseFloat(item.dataset.lat);
        var lng = parseFloat(item.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) showLocationInfo(item.dataset.name, lat, lng, '<small class="text-muted">Vessel</small>');
    }
});

loadVesselList('');

var vesselSearchDebounce = null;

function searchVesselsLocal(query) {
    var resultsDiv = document.getElementById('vesselSearchResults');
    var statusDiv = document.getElementById('vesselSearchStatus');

    if (!query || query.length < 2) {
        resultsDiv.style.display = 'none';
        statusDiv.style.display = 'none';
        return;
    }

    statusDiv.style.display = 'block';
    statusDiv.textContent = 'Searching...';

    clearTimeout(vesselSearchDebounce);
    vesselSearchDebounce = setTimeout(function() {
        fetch('/api/portmap/search-vessels?q=' + encodeURIComponent(query) + '&limit=15')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.results || data.results.length === 0) {
                    resultsDiv.style.display = 'none';
                    statusDiv.textContent = 'No vessels found';
                    return;
                }

                statusDiv.textContent = data.results.length + ' results';
                resultsDiv.style.display = 'block';
                resultsDiv.innerHTML = data.results.map(function(v) {
                    var typeColors = { container: '#0d6efd', tanker: '#dc3545', bulk: '#198754', lng: '#0dcaf0' };
                    var color = typeColors[v.vessel_type] || '#6c757d';
                    var hasPos = v.latitude && v.longitude;
                    var mtUrl = hasPos ? 'https://www.marinetraffic.com/en/ais/embed/zoom:10/centery:' + v.latitude + '/centerx:' + v.longitude + '/maptype:0/shownames:true/showmenu:false/remember:false' : '';

                    return '<div class="list-group-item list-group-item-action py-2 px-2 vessel-search-item" style="cursor:pointer;border-left:3px solid ' + color + ';" ' +
                        'data-lat="' + (v.latitude || '') + '" data-lng="' + (v.longitude || '') + '" data-name="' + (v.name || '').replace(/"/g, '&quot;') + '">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                        '<div style="flex:1;">' +
                        '<div class="fw-semibold" style="font-size:0.82rem;">' + v.name + '</div>' +
                        '<small class="text-muted" style="font-size:0.72rem;">' + v.mmsi + (v.flag_country ? ' &middot; ' + v.flag_country : '') + '</small>' +
                        '</div>' +
                        '<div class="d-flex gap-1">' +
                        (hasPos ? '<a href="' + mtUrl + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success py-0 px-1" style="font-size:0.7rem;" title="Buka di MarineTraffic"><i class="bi bi-box-arrow-up-right"></i></a>' : '') +
                        (hasPos ? '<button class="btn btn-sm btn-outline-primary py-0 px-1 vessel-goto-btn" style="font-size:0.7rem;" data-lat="' + v.latitude + '" data-lng="' + v.longitude + '" data-name="' + (v.name || '').replace(/"/g, '&quot;') + '"><i class="bi bi-info-circle"></i></button>' : '') +
                        '</div>' +
                        '</div>' +
                        '</div>';
                }).join('');
            })
            .catch(function() {
                statusDiv.textContent = 'Search failed';
            });
    }, 300);
}

document.getElementById('vesselSearchInput').addEventListener('input', function() {
    searchVesselsLocal(this.value);
});

document.getElementById('vesselSearchBtn').addEventListener('click', function() {
    searchVesselsLocal(document.getElementById('vesselSearchInput').value);
});

document.getElementById('vesselSearchResults').addEventListener('click', function(e) {
    var gotoBtn = e.target.closest('.vessel-goto-btn');
    if (gotoBtn) {
        e.stopPropagation();
        var lat = parseFloat(gotoBtn.dataset.lat);
        var lng = parseFloat(gotoBtn.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
            showLocationInfo(gotoBtn.dataset.name, lat, lng, '<small class="text-muted">Vessel</small>');
        }
        return;
    }
    var item = e.target.closest('.vessel-search-item');
    if (item && item.dataset.lat && item.dataset.lng) {
        var lat = parseFloat(item.dataset.lat);
        var lng = parseFloat(item.dataset.lng);
        var mmsi = item.querySelector('small') ? item.querySelector('small').textContent.split(' ')[0] : '';
        if (!isNaN(lat) && !isNaN(lng)) {
            var color = item.style.borderLeftColor || '#6c757d';
            showLocationInfo(item.dataset.name, lat, lng,
                '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + color + ';margin-right:4px;"></span>' +
                '<small>MMSI: ' + mmsi + '</small>');
        }
    }
});
</script>
@endsection
