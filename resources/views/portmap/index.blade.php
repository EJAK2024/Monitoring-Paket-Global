@extends('layouts.app')

@section('title', 'Vessel Monitoring — Port Intelligence')

@section('content')
<style>
    .content { padding: 0 !important; }
</style>
<div class="position-relative" style="height: 100vh;">
    <div id="portMap" style="height: 100%; width: 100%; border-radius: 0;"></div>

    {{-- ======= SIDEBAR ======= --}}
    <div id="sidebar" class="position-absolute top-0 start-0 shadow" style="z-index: 1020; width: 380px; height: 100vh; background: rgba(255,255,255,0.97); backdrop-filter: blur(8px); transition: transform 0.3s ease; display: flex; flex-direction: column;">
        {{-- Sidebar header --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="background: #f8f9fc;">
            <div>
                <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap-fill me-1 text-primary"></i>Marine Monitor</h6>
                <small class="text-muted" id="sidebarStatus">Loading...</small>
            </div>
            <button id="sidebarToggleBtn" class="btn btn-sm btn-outline-secondary rounded-circle" style="width:32px;height:32px;" title="Sembunyikan sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>

        {{-- Sidebar tabs --}}
        <ul class="nav nav-tabs nav-fill small" id="sidebarTabs" role="tablist" style="font-size:0.8rem;">
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

        {{-- Sidebar content --}}
        <div class="tab-content flex-grow-1 overflow-auto" style="font-size:0.85rem;">
            {{-- Tab: Search --}}
            <div class="tab-pane fade show active p-3" id="panel-search" role="tabpanel">
                <input type="text" id="portSearch" class="form-control form-control-sm mb-2" placeholder="Search port name or country...">
                <select id="portTypeFilter" class="form-select form-select-sm mb-2">
                    <option value="">All port types</option>
                    @foreach ($portTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <div id="searchResults" class="list-group mb-2" style="max-height: 300px; overflow-y: auto; display: none;"></div>
                <div class="d-flex justify-content-between small text-muted border-top pt-2">
                    <span id="portCount">-</span>
                    <span id="routeCount">-</span>
                </div>
            </div>

            {{-- Tab: Ports --}}
            <div class="tab-pane fade p-3" id="panel-ports" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Port Directory</span>
                    <span id="portCount2" class="badge bg-primary rounded-pill">0</span>
                </div>
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="bi bi-filter"></i></span>
                    <select id="portTypeFilter2" class="form-select">
                        <option value="">All types</option>
                        @foreach ($portTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="portList" style="max-height: 350px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">Loading ports...</div>
                </div>
            </div>

            {{-- Tab: Vessels --}}
            <div class="tab-pane fade p-3" id="panel-vessels" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Active Vessels</span>
                    <span id="shipCount2" class="badge bg-success rounded-pill">0</span>
                </div>
                <div class="mb-2">
                    <select id="vesselTypeFilter" class="form-select form-select-sm">
                        <option value="">All types</option>
                        <option value="container">Container Ship</option>
                        <option value="tanker">Tanker</option>
                        <option value="bulk">Bulk Carrier</option>
                        <option value="lng">LNG Carrier</option>
                    </select>
                </div>
                <div id="vesselList" style="max-height: 350px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">Initializing vessels...</div>
                </div>
            </div>

            {{-- Tab: Layers --}}
            <div class="tab-pane fade p-3" id="panel-layers" role="tabpanel">
                <div class="fw-semibold mb-2">Map Overlays</div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="layerRoutes" checked>
                    <label class="form-check-label" for="layerRoutes">Trade routes</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="layerShips" checked>
                    <label class="form-check-label" for="layerShips">Simulated vessels</label>
                </div>
                <hr>
                <div class="fw-semibold mb-2">Legend</div>
                <div class="small">
                    <div class="mb-1"><span class="d-inline-block" style="width:10px;height:10px;border-radius:50%;background:#0d6efd;"></span> Container port</div>
                    <div class="mb-1"><span class="d-inline-block" style="width:10px;height:10px;border-radius:50%;background:#198754;"></span> Energy port</div>
                    <div class="mb-1"><span class="d-inline-block" style="width:10px;height:10px;border-radius:50%;background:#ffc107;"></span> Industrial port</div>
                    <div class="mb-1"><span class="d-inline-block" style="width:10px;height:10px;border-radius:50%;background:#dc3545;"></span> Multi-purpose port</div>
                    <hr class="my-1">
                    <div class="mb-1"><span style="font-size:14px;color:#0d6efd;">▷</span> Container vessel</div>
                    <div class="mb-1"><span style="font-size:14px;color:#dc3545;">◇</span> Tanker</div>
                    <div class="mb-1"><span style="font-size:14px;color:#198754;">◁</span> Bulk carrier</div>
                    <div class="mb-1"><span style="font-size:14px;color:#0dcaf0;">○</span> LNG carrier</div>
                    <hr class="my-1">
                    <div><span style="font-size:14px;">- - -</span> Trade route</div>
                </div>
            </div>
        </div>

        {{-- Sidebar footer --}}
        <div class="p-2 border-top text-center small text-muted">
            <a href="{{ route('dashboard') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Dashboard</a>
            <span class="mx-2">|</span>
            <span id="sidebarFooterInfo">Memuat...</span>
        </div>
    </div>

    {{-- Sidebar collapse toggle (floating) --}}
    <button id="sidebarOpenBtn" class="position-absolute btn btn-primary shadow-sm" style="z-index: 1010; display: none; top: 20px; left: 20px; border-radius: 50%; width: 44px; height: 44px; padding: 0; font-size: 1.3rem; cursor: pointer;" title="Tampilkan sidebar">
        <i class="bi bi-arrow-bar-right"></i>
    </button>

    {{-- Ship detail panel (hidden by default) --}}
    <div id="shipPanel" class="position-absolute bottom-0 start-0 p-3" style="z-index: 1000; display: none;">
        <div class="card shadow" style="width: 340px; background: rgba(255,255,255,0.97);">
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
// ============================================================
//  GLOBALS — no Leaflet dependency
// ============================================================
const TYPE_COLORS = {
    Container: '#0d6efd',
    Energy: '#198754',
    Industrial: '#ffc107',
    'Multi-purpose': '#dc3545',
};

const SHIP_TYPES = {
    container: { color: '#0d6efd', shape: '\u25b7', label: 'Container Ship', speedBase: 0.030, size: 22 },
    tanker:    { color: '#dc3545', shape: '\u25c7', label: 'Tanker',        speedBase: 0.018, size: 20 },
    bulk:      { color: '#198754', shape: '\u25c1', label: 'Bulk Carrier',  speedBase: 0.015, size: 20 },
    lng:       { color: '#0dcaf0', shape: '\u25cb', label: 'LNG Carrier',   speedBase: 0.022, size: 18 },
};

const SHIP_NAMES = [
    'MV Emma Maersk', 'MV CMA CGM Antoine', 'MV MSC Irina', 'MV OOCL Hong Kong',
    'MV Ever Given', 'MV COSCO Shipping Universe', 'MV HMM Algeciras', 'MV ONE Blue',
    'MV MOL Triumph', 'MV MSC Zoe', 'MV CMA CGM Marco Polo', 'MV Maersk Mc-Kinney',
    'MV Ever Ace', 'MV HMM Oslo', 'MV OOCL Germany', 'MV COSCO Pride',
    'MT Knock Nevis', 'MT TI Europe', 'MT TI Asia', 'MT Batillus',
    'MT Berge Stahl', 'MT Seawise Giant', 'MT Hellespont Alhambra', 'MT Hellespont Fairfax',
    'MV CSCL Globe', 'MV Madrid Maersk', 'MV CMA CGM Jacques Saad\u00e9', 'MV OOCL Indonesia',
    'MV Ever Bloom', 'MV ONE Stork', 'MV YM Wellbeing', 'MV Wan Hai A01',
    'MV ZINGA', 'MV Interasia Vision', 'MV TS Yokohama', 'MV SITC Qingdao',
    'MV Kota Cepat', 'MV Cap Sankt George', 'MV Cape Town', 'MV Northern Jaguar',
    'MT Almi Star', 'MT Diamond Ocean', 'MT Silver Lake', 'MT Nautica',
    'MV APL Singapura', 'MV Hyundai Ocean', 'MV Seaspan Lion', 'MV Yang Ming Wellbeing',
    'MV Ever Lotus', 'MV ONE Orion', 'MV HMM Copenhagen', 'MV COSCO France',
    'MT Pacific Ruby', 'MT Eagle Star', 'MT Sinar Mas', 'MT BW Oak',
    'MV Maersk Chennai', 'MV CMA CGM America', 'MV MSC Diana', 'MV OOCL Brussels',
];

let portMap, clusterGroup, routeLineLayer, shipLayer;
let allPorts = [];
let routeData = @json($routes);
let ships = [];
let routeLines = [];
let shipIdCounter = 1;
let selectedShip = null;
let followShip = null;
let highlightedRouteLine = null;
let sidebarEl, sidebarToggleBtn, sidebarOpenBtn;
let sidebarHidden = false;

// ============================================================
//  PORT ICONS
// ============================================================
function createPortIcon(type) {
    const color = TYPE_COLORS[type] || '#6c757d';
    return L.divIcon({
        className: '',
        html: `<div style="width:12px;height:12px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 0 4px rgba(0,0,0,0.3);"></div>`,
        iconSize: [12, 12],
        iconAnchor: [6, 6],
        popupAnchor: [0, -8],
    });
}

// ============================================================
//  LOAD PORTS
// ============================================================
function loadPorts(type) {
    const url = type ? `/api/portmap/ports?type=${encodeURIComponent(type)}` : '/api/portmap/ports';
    clusterGroup.clearLayers();

    fetch(url)
        .then(r => r.json())
        .then(ports => {
            allPorts = ports;
            document.getElementById('portCount').textContent = `${ports.length} ports`;

            const markers = [];
            for (const p of ports) {
                const lat = parseFloat(p.latitude);
                const lon = parseFloat(p.longitude);
                if (isNaN(lat) || isNaN(lon)) continue;

                markers.push(L.marker([lat, lon], { icon: createPortIcon(p.port_type) })
                    .bindPopup(`
                        <div style="min-width:180px;">
                            <h6 style="margin:0 0 4px;">${p.name}</h6>
                            <div style="font-size:0.85rem;color:#555;">
                                ${p.country}<br>
                                <span class="badge bg-${p.port_type === 'Container' ? 'primary' : p.port_type === 'Energy' ? 'success' : p.port_type === 'Industrial' ? 'warning text-dark' : 'danger'}">${p.port_type || 'N/A'}</span>
                            </div>
                        </div>
                    `));
            }
            clusterGroup.addLayers(markers);
        });
}

// ============================================================
//  SEARCH / FLY
// ============================================================
function flyToPort(lat, lon, name) {
    portMap.flyTo([lat, lon], 8, { duration: 1 });
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('portSearch').value = name || '';
}

function pickRandom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

// ============================================================
//  SHIP HELPERS
// ============================================================
function getWaypointAtProgress(waypoints, progress) {
    const totalSeg = waypoints.length - 1;
    const totalP = progress * totalSeg;
    const segIdx = Math.min(Math.floor(totalP), totalSeg - 1);
    const segP = totalP - segIdx;
    const from = waypoints[segIdx];
    const to = waypoints[segIdx + 1];
    const lat = from[0] + (to[0] - from[0]) * segP;
    const lng = from[1] + (to[1] - from[1]) * segP;
    let heading = Math.atan2(to[1] - from[1], to[0] - from[0]) * 180 / Math.PI;
    heading = ((heading % 360) + 360) % 360;
    return { lat, lng, heading, segIdx, segP, from, to };
}

function getDestinationPortName(waypoints, progress) {
    const totalSeg = waypoints.length - 1;
    const totalP = progress * totalSeg;
    const segIdx = Math.floor(totalP);
    const nextIdx = Math.min(segIdx + 1, totalSeg);
    const wp = waypoints[nextIdx];
    for (const p of allPorts) {
        const d = Math.abs(parseFloat(p.latitude) - wp[0]) + Math.abs(parseFloat(p.longitude) - wp[1]);
        if (d < 0.5) return p.name;
    }
    return `${wp[0].toFixed(1)}\u00b0${wp[0] >= 0 ? 'N' : 'S'}, ${wp[1].toFixed(1)}\u00b0${wp[1] >= 0 ? 'E' : 'W'}`;
}

function getOriginPortName(waypoints, progress) {
    const totalSeg = waypoints.length - 1;
    const totalP = progress * totalSeg;
    const segIdx = Math.floor(totalP);
    const prevIdx = Math.max(segIdx, 0);
    const prevProgress = Math.max(progress - 0.05 / (totalSeg + 1), 0);
    return getDestinationPortName(waypoints, prevProgress);
}

function getShipStatus(ship) {
    return 'Underway';
}

// ============================================================
//  SHIP MARKERS
// ============================================================
function buildShipMarkers() {
    shipLayer.clearLayers();

    ships.forEach(ship => {
        const pos = getWaypointAtProgress(ship.waypoints, ship.progress);
        const marker = L.marker([pos.lat, pos.lng], {
            icon: L.divIcon({
                className: '',
                html: `<div style="font-size:${ship.type.size}px;line-height:1;color:${ship.type.color};text-shadow:0 0 4px rgba(0,0,0,0.5);transform:rotate(${pos.heading}deg);transition:transform 0.3s;cursor:pointer;">${ship.type.shape}</div>`,
                iconSize: [ship.type.size, ship.type.size],
                iconAnchor: [ship.type.size / 2, ship.type.size / 2],
            }),
            interactive: true,
            zIndexOffset: 10000 + ship.id,
        });

        marker.bindPopup(`
            <div style="min-width:200px;">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${ship.name}</strong>
                    <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'}">${ship.type.label}</span>
                </div>
                <hr style="margin:4px 0;">
                <div style="font-size:0.85rem;">
                    <div>Route: <strong>${routeData[ship.routeIndex].name}</strong></div>
                    <div>Speed: <strong>${(ship.speed * 1000).toFixed(0)}</strong> knots</div>
                    <div>Heading: <strong>${pos.heading.toFixed(0)}\u00b0</strong></div>
                    <div>Destination: <strong>${getDestinationPortName(ship.waypoints, ship.progress)}</strong></div>
                    <div>Status: <span class="text-success">\u25cf Underway</span></div>
                </div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="selectShip(${ship.id})">Track this vessel</button>
            </div>
        `, { maxWidth: 260 });

        marker.on('click', function () {
            selectShip(ship.id);
        });

        ship.marker = marker;
        shipLayer.addLayer(marker);
    });

    document.getElementById('shipCount2').textContent = ships.length;
    document.getElementById('sidebarStatus').textContent = `${ships.length} ships tracked`;
}

// ============================================================
//  ANIMATION LOOP
// ============================================================
function animateShips() {
    for (const ship of ships) {
        ship.progress += ship.speed * 0.008;
        if (ship.progress >= 1) ship.progress = 0;

        if (!ship.marker) continue;
        const pos = getWaypointAtProgress(ship.waypoints, ship.progress);

        const iconEl = ship.marker.getElement();
        if (iconEl) {
            const inner = iconEl.querySelector('div');
            if (inner) {
                inner.style.transform = `rotate(${pos.heading}deg)`;
            }
        }

        ship.marker.setLatLng([pos.lat, pos.lng]);

        if (ship.marker.isPopupOpen()) {
            ship.marker.setPopupContent(`
                <div style="min-width:200px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${ship.name}</strong>
                        <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'}">${ship.type.label}</span>
                    </div>
                    <hr style="margin:4px 0;">
                    <div style="font-size:0.85rem;">
                        <div>Route: <strong>${routeData[ship.routeIndex].name}</strong></div>
                        <div>Speed: <strong>${(ship.speed * 1000).toFixed(0)}</strong> knots</div>
                        <div>Heading: <strong>${pos.heading.toFixed(0)}\u00b0</strong></div>
                        <div>Destination: <strong>${getDestinationPortName(ship.waypoints, ship.progress)}</strong></div>
                        <div>Status: <span class="text-success">\u25cf Underway</span></div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="selectShip(${ship.id})">Track this vessel</button>
                </div>
            `);
        }
    }

    if (followShip && followShip.marker) {
        const ll = followShip.marker.getLatLng();
        portMap.setView([ll.lat, ll.lng], portMap.getZoom(), { animate: false });
    }

    requestAnimationFrame(animateShips);
}

// ============================================================
//  SHIP UI
// ============================================================
function selectShip(id) {
    const ship = ships.find(s => s.id === id);
    if (!ship) return;
    selectedShip = ship;

    const pos = getWaypointAtProgress(ship.waypoints, ship.progress);
    const panel = document.getElementById('shipPanel');
    panel.style.display = 'block';

    document.getElementById('shipPanelName').textContent = ship.name;
    document.getElementById('shipPanelType').textContent = ship.type.label;
    document.getElementById('shipPanelSpeed').textContent = (ship.speed * 1000).toFixed(0) + ' knots';
    document.getElementById('shipPanelHeading').textContent = pos.heading.toFixed(0) + '\u00b0';
    document.getElementById('shipPanelRoute').textContent = routeData[ship.routeIndex].name;
    document.getElementById('shipPanelDest').textContent = getDestinationPortName(ship.waypoints, ship.progress);
    document.getElementById('shipPanelStatus').textContent = '\u25cf Underway';

    if (ship.marker) {
        ship.marker.openPopup();
    }
}

function closeShipPanel() {
    document.getElementById('shipPanel').style.display = 'none';
    if (selectedShip && selectedShip.marker) {
        selectedShip.marker.closePopup();
    }
    selectedShip = null;
    followShip = null;
    document.getElementById('shipFollowBtn').innerHTML = '<i class="bi bi-crosshair"></i> Follow';
}

function toggleFollowShip() {
    if (followShip) {
        followShip = null;
        document.getElementById('shipFollowBtn').innerHTML = '<i class="bi bi-crosshair"></i> Follow';
    } else if (selectedShip) {
        followShip = selectedShip;
        document.getElementById('shipFollowBtn').innerHTML = '<i class="bi bi-eye-slash"></i> Unfollow';
    }
}

function highlightShipRoute() {
    if (!selectedShip) return;

    if (highlightedRouteLine) {
        routeLineLayer.addLayer(highlightedRouteLine);
    }

    for (const line of routeLines) {
        routeLineLayer.removeLayer(line);
    }

    routeLines.forEach((line, idx) => {
        if (idx === selectedShip.routeIndex) {
            routeLineLayer.addLayer(line);
            line.setStyle({ opacity: 0.9, weight: 3, color: selectedShip.type.color, dashArray: null });
            highlightedRouteLine = line;
        } else {
            line.setStyle({ opacity: 0.15, weight: 1, color: '#6c757d', dashArray: '6, 8' });
            routeLineLayer.addLayer(line);
        }
    });

    const route = routeData[selectedShip.routeIndex];
    portMap.flyToBounds(L.latLngBounds(route.waypoints.map(w => [w[0], w[1]])), { padding: [50, 50] });
}

function renderPortList() {
    const typeFilter = document.getElementById('portTypeFilter2').value;
    const list = document.getElementById('portList');
    const filtered = typeFilter ? allPorts.filter(p => p.port_type === typeFilter) : allPorts;

    document.getElementById('portCount2').textContent = filtered.length;

    if (!filtered.length) {
        list.innerHTML = '<div class="text-center text-muted py-4">No ports found</div>';
        return;
    }

    list.innerHTML = filtered.map(p => `
        <div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom" style="cursor:pointer;"
             onclick="flyToPort(${p.latitude}, ${p.longitude}, '${p.name.replace(/'/g, "\\'")}')">
            <div>
                <div class="fw-semibold" style="font-size:0.85rem;">${p.name}</div>
                <small class="text-muted">${p.country}</small>
            </div>
            <span class="badge bg-${p.port_type === 'Container' ? 'primary' : p.port_type === 'Energy' ? 'success' : p.port_type === 'Industrial' ? 'warning text-dark' : 'danger'} rounded-pill" style="font-size:0.7rem;">${p.port_type || 'N/A'}</span>
        </div>
    `).join('');
}

function renderVesselList() {
    const typeFilter = document.getElementById('vesselTypeFilter').value;
    const list = document.getElementById('vesselList');
    const filtered = typeFilter ? ships.filter(s => s.typeKey === typeFilter) : ships;

    document.getElementById('shipCount2').textContent = filtered.length;

    if (!filtered.length) {
        list.innerHTML = '<div class="text-center text-muted py-4">No vessels</div>';
        return;
    }

    list.innerHTML = filtered.map(ship => {
        const pos = getWaypointAtProgress(ship.waypoints, ship.progress);
        return `
            <div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom" style="cursor:pointer;"
                 onclick="selectShip(${ship.id})">
                <div>
                    <div class="fw-semibold" style="font-size:0.85rem;">
                        <span style="color:${ship.type.color};margin-right:4px;">${ship.type.shape}</span>
                        ${ship.name}
                    </div>
                    <small class="text-muted">${routeData[ship.routeIndex].name} \u00b7 ${pos.heading.toFixed(0)}\u00b0</small>
                </div>
                <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'} rounded-pill" style="font-size:0.7rem;">${ship.type.label}</span>
            </div>
        `;
    }).join('');
}

// ============================================================
//  INIT — deferred until Leaflet is ready
// ============================================================
(function init() {
    if (typeof L === 'undefined') return setTimeout(init, 50);

    // ---- Map ----
    portMap = L.map('portMap', { zoomControl: true }).setView([20, 30], 2);
    L.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key={{ config('services.maptiler.key') }}', {
        tileSize: 512,
        zoomOffset: -1,
        minZoom: 1,
        attribution: '<a href="https://www.maptiler.com/copyright/" target="_blank">&copy; MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">&copy; OpenStreetMap contributors</a>',
        crossOrigin: true,
    }).addTo(portMap);

    clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 10,
    });
    portMap.addLayer(clusterGroup);

    routeLineLayer = L.layerGroup().addTo(portMap);
    shipLayer = L.layerGroup().addTo(portMap);

    // ---- Route count ----
    document.getElementById('routeCount').textContent = `${routeData.length} routes`;

    // ---- Build ships from route data ----
    routeData.forEach(route => {
        const polyline = L.polyline(route.waypoints, {
            color: '#6c757d',
            weight: 1.5,
            opacity: 0.35,
            dashArray: '6, 8',
            interactive: false,
        });
        routeLineLayer.addLayer(polyline);
        routeLines.push(polyline);

        for (let i = 0; i < route.ships; i++) {
            const typeKey = route.type === 'tanker' ? 'tanker'
                          : route.type === 'bulk' ? 'bulk'
                          : Math.random() < 0.15 ? 'tanker'
                          : Math.random() < 0.1 ? 'bulk'
                          : Math.random() < 0.08 ? 'lng'
                          : 'container';
            const type = SHIP_TYPES[typeKey];
            const name = SHIP_NAMES[shipIdCounter % SHIP_NAMES.length];
            const speedVariation = 0.7 + Math.random() * 0.6;
            const shipSpeed = route.type === typeKey ? type.speedBase * speedVariation : type.speedBase * 0.8 * speedVariation;

            const initProgress = Math.random();

            const ship = {
                id: shipIdCounter++,
                name: name,
                typeKey: typeKey,
                type: type,
                routeIndex: routeData.indexOf(route),
                waypoints: route.waypoints,
                progress: initProgress,
                speed: shipSpeed,
                baseSpeed: shipSpeed,
                originName: route.name,
            };

            ships.push(ship);
        }
    });

    // ---- Sidebar DOM refs ----
    sidebarEl = document.getElementById('sidebar');
    sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    sidebarOpenBtn = document.getElementById('sidebarOpenBtn');

    // ---- Search & filter ----
    document.getElementById('portTypeFilter').addEventListener('change', function () {
        loadPorts(this.value);
    });

    document.getElementById('portSearch').addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        const results = document.getElementById('searchResults');
        if (!term) { results.style.display = 'none'; return; }

        const filtered = allPorts.filter(p =>
            p.name.toLowerCase().includes(term) ||
            p.country.toLowerCase().includes(term) ||
            (p.port_type || '').toLowerCase().includes(term)
        );
        if (!filtered.length) { results.style.display = 'none'; return; }

        results.style.display = 'block';
        results.innerHTML = filtered.slice(0, 25).map(p => `
            <button type="button" class="list-group-item list-group-item-action py-1 px-2" style="font-size:0.85rem;cursor:pointer;"
                onclick="flyToPort(${p.latitude}, ${p.longitude}, '${p.name.replace(/'/g, "\\'")}')">
                ${p.name} <span class="text-muted">(${p.country})</span>
            </button>
        `).join('');
    });

    // ---- Sidebar tabs — Ports list ----
    document.getElementById('tab-ports').addEventListener('shown.bs.tab', function () {
        renderPortList();
    });

    document.getElementById('portTypeFilter2').addEventListener('change', function () {
        renderPortList();
    });

    // ---- Sidebar tabs — Vessels list ----
    document.getElementById('tab-vessels').addEventListener('shown.bs.tab', function () {
        renderVesselList();
    });

    document.getElementById('vesselTypeFilter').addEventListener('change', function () {
        renderVesselList();
    });

    // ---- Layer checkboxes ----
    document.getElementById('layerRoutes').addEventListener('change', function () {
        if (this.checked) { portMap.addLayer(routeLineLayer); } else { portMap.removeLayer(routeLineLayer); }
    });

    document.getElementById('layerShips').addEventListener('change', function () {
        if (this.checked) { portMap.addLayer(shipLayer); } else { portMap.removeLayer(shipLayer); }
    });

    // ---- Sidebar toggle ----
    sidebarToggleBtn.addEventListener('click', function () {
        sidebarHidden = !sidebarHidden;
        sidebarEl.style.transform = sidebarHidden ? 'translateX(-100%)' : 'translateX(0)';
        sidebarOpenBtn.style.display = sidebarHidden ? 'block' : 'none';
        sidebarToggleBtn.querySelector('i').className = sidebarHidden ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
        setTimeout(() => portMap.invalidateSize(), 350);
    });

    sidebarOpenBtn.addEventListener('click', function () {
        sidebarHidden = false;
        sidebarEl.style.transform = 'translateX(0)';
        sidebarOpenBtn.style.display = 'none';
        sidebarToggleBtn.querySelector('i').className = 'bi bi-chevron-left';
        setTimeout(() => portMap.invalidateSize(), 350);
    });

    // ---- Map events ----
    portMap.on('click', function () {
        if (selectedShip) closeShipPanel();
    });

    // ---- Start ----
    loadPorts('');
    buildShipMarkers();
    animateShips();

    // ---- Resize ----
    setTimeout(() => portMap.invalidateSize(), 200);
    window.addEventListener('resize', () => portMap.invalidateSize());

    // ---- Status ----
    document.getElementById('sidebarStatus').textContent = `${ships.length} ships tracked`;
    document.getElementById('sidebarFooterInfo').textContent = `${allPorts.length || '...'} ports · ${ships.length} vessels`;
})();
</script>
@if (count($routes) === 0)
<script>
    (function(){
        var el = document.getElementById('sidebarStatus');
        if (el) el.textContent = 'No routes — seed ports first';
    })();
</script>
@endif
