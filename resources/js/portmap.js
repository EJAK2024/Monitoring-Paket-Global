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

const NAVSTATUS_MAP = {
    0: { text: 'Underway', class: 'text-success' },
    1: { text: 'At anchor', class: 'text-warning' },
    2: { text: 'Not under command', class: 'text-danger' },
    3: { text: 'Restricted', class: 'text-warning' },
    4: { text: 'Constrained', class: 'text-warning' },
    5: { text: 'Moored', class: 'text-secondary' },
    6: { text: 'Aground', class: 'text-danger' },
    7: { text: 'Fishing', class: 'text-info' },
    8: { text: 'Sailing', class: 'text-info' },
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
let routeData = window.__ROUTES;
let ships = [];
let routeLines = [];
let shipIdCounter = 1;
let selectedShip = null;
let followShip = null;
let highlightedRouteLine = null;
let sidebarEl, sidebarToggleBtn, sidebarOpenBtn;
let sidebarHidden = false;
let animationId = null;
let usingLiveData = window.__HAS_LIVE;
let liveVesselData = window.__LIVE_VESSELS;
let lastVesselFetch = null;
let apiStatus = window.__API_STATUS;
let trackedVessels = [];
let vesselSearchDebounce = null;
let trackedRefreshInterval = null;
const MAPTILER_KEY = window.__MAPTILER_KEY;

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

                const marker = L.marker([lat, lon], { icon: createPortIcon(p.port_type) })
                    .bindPopup(`
                        <div style="min-width:180px;">
                            <h6 style="margin:0 0 4px;">${p.name}</h6>
                            <div style="font-size:0.85rem;color:#555;">
                                ${p.country}<br>
                                <span class="badge bg-${p.port_type === 'Container' ? 'primary' : p.port_type === 'Energy' ? 'success' : p.port_type === 'Industrial' ? 'warning text-dark' : 'danger'}">${p.port_type || 'N/A'}</span>
                            </div>
                        </div>
                    `)
                    .on('click', function () {
                        flyToPort(lat, lon, p.name);
                    });
                markers.push(marker);
            }
            clusterGroup.addLayers(markers);

            document.getElementById('sidebarFooterInfo').textContent =
                `${ports.length} ports · ${ships.length} vessels` +
                (usingLiveData ? ' · LIVE' : '');
        });
}

function flyToPort(lat, lon, name) {
    var map = window._portMap || portMap;
    if (!map) return;
    map.invalidateSize();
    map.setView([lat, lon], 8, { animate: true });
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('portSearch').value = name || '';
}

function pickRandom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

function getWaypointAtProgress(waypoints, progress) {
    const totalSeg = waypoints.length - 1;
    if (totalSeg <= 0) return { lat: waypoints[0][0], lng: waypoints[0][1], heading: 0, segIdx: 0, segP: 0, from: waypoints[0], to: waypoints[0] };
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
    if (totalSeg <= 0) return 'Unknown';
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

function getShipStatus(ship) {
    if (ship.navstat !== undefined && NAVSTATUS_MAP[ship.navstat]) {
        return NAVSTATUS_MAP[ship.navstat].text;
    }
    return 'Underway';
}

function getShipStatusClass(ship) {
    if (ship.navstat !== undefined && NAVSTATUS_MAP[ship.navstat]) {
        return NAVSTATUS_MAP[ship.navstat].class;
    }
    return 'text-success';
}

function fetchLiveVessels() {
    fetch('/api/portmap/vessels')
        .then(r => r.json())
        .then(data => {
            if (data.live && data.live.length > 0) {
                usingLiveData = true;
                liveVesselData = data.live;
                lastVesselFetch = data.timestamp;

                document.getElementById('vesselUpdateInfo').textContent =
                    'Updated: ' + new Date(data.timestamp).toLocaleTimeString();

                mergeLiveVessels();
                buildShipMarkers();
                renderVesselList();

                document.getElementById('sidebarStatus').textContent =
                    `${ships.length} ships tracked · Live`;
                document.getElementById('sidebarFooterInfo').textContent =
                    `${allPorts.length || '...'} ports · ${ships.length} vessels · LIVE`;
            }
        })
        .catch(() => {});
}

function mergeLiveVessels() {
    if (!usingLiveData || !liveVesselData.length) return;

    const existingIds = new Set(ships.map(s => s.mmsi));

    for (const lv of liveVesselData) {
        const lat = parseFloat(lv.latitude);
        const lon = parseFloat(lv.longitude);
        if (isNaN(lat) || isNaN(lon)) continue;

        if (existingIds.has(lv.mmsi)) continue;

        const typeKey = lv.speed > 12 ? 'container'
                      : lv.speed > 8 ? 'tanker'
                      : lv.speed > 5 ? 'bulk'
                      : 'container';
        const type = SHIP_TYPES[typeKey] || SHIP_TYPES.container;

        const nearestRoute = findNearestRoute(lat, lon);
        const routeIndex = nearestRoute.index;

        let waypoints = [];
        let progress = 0;
        let speed = 0;

        if (nearestRoute.route && nearestRoute.route.waypoints.length >= 2) {
            waypoints = nearestRoute.route.waypoints;
            progress = nearestRoute.progress;
            speed = nearestRoute.route.speed;
        } else if (routeData.length > 0) {
            const ri = routeIndex >= 0 ? routeIndex : 0;
            waypoints = routeData[ri].waypoints;
            progress = Math.random();
            speed = routeData[ri].speed || 0.025;
        }

        const ship = {
            id: shipIdCounter++,
            mmsi: lv.mmsi,
            name: lv.name || 'Unknown',
            typeKey: typeKey,
            type: type,
            routeIndex: routeIndex >= 0 ? routeIndex : 0,
            waypoints: waypoints,
            progress: progress,
            speed: speed,
            baseSpeed: speed,
            navstat: lv.status,
            isLive: true,
            destination: lv.destination || '',
            realLat: lat,
            realLng: lon,
        };

        ships.push(ship);
        existingIds.add(lv.mmsi);
    }
}

function findNearestRoute(lat, lng) {
    let best = { index: -1, route: null, progress: 0, dist: Infinity };

    for (let i = 0; i < routeData.length; i++) {
        const route = routeData[i];
        for (let j = 0; j < route.waypoints.length - 1; j++) {
            const a = route.waypoints[j];
            const b = route.waypoints[j + 1];

            const segLen = Math.sqrt(Math.pow(b[0] - a[0], 2) + Math.pow(b[1] - a[1], 2));
            if (segLen < 0.01) continue;

            const t = Math.max(0, Math.min(1, (
                (lat - a[0]) * (b[0] - a[0]) + (lng - a[1]) * (b[1] - a[1])
            ) / (segLen * segLen)));

            const projLat = a[0] + t * (b[0] - a[0]);
            const projLng = a[1] + t * (b[1] - a[1]);
            const dist = Math.sqrt(Math.pow(lat - projLat, 2) + Math.pow(lng - projLng, 2));

            if (dist < best.dist) {
                const totalSeg = route.waypoints.length - 1;
                const progress = (j + t) / totalSeg;
                best = { index: i, route, progress: Math.min(progress, 0.99), dist };
            }
        }
    }

    return best;
}

function buildShipMarkers() {
    shipLayer.clearLayers();

    ships.forEach(ship => {
        let pos;
        if (ship.isLive && ship.realLat !== undefined) {
            pos = { lat: ship.realLat, lng: ship.realLng, heading: ship.heading || 0 };
        } else {
            pos = getWaypointAtProgress(ship.waypoints, ship.progress);
        }

        const isTracked = ship.isTracked;
        const markerColor = isTracked ? '#f59e0b' : ship.type.color;
        const markerSize = isTracked ? ship.type.size + 4 : ship.type.size;
        const glowStyle = isTracked ? 'filter: drop-shadow(0 0 6px rgba(245,158,11,0.8));' : '';

        const marker = L.marker([pos.lat, pos.lng], {
            icon: L.divIcon({
                className: '',
                html: `<div style="font-size:${markerSize}px;line-height:1;color:${markerColor};text-shadow:0 0 4px rgba(0,0,0,0.5);transform:rotate(${pos.heading}deg);transition:transform 0.3s;cursor:pointer;${glowStyle}">${isTracked ? '\u25b6' : ship.type.shape}</div>`,
                iconSize: [markerSize, markerSize],
                iconAnchor: [markerSize / 2, markerSize / 2],
            }),
            interactive: true,
            zIndexOffset: isTracked ? 20000 + ship.id : 10000 + ship.id,
        });

        const navStatus = getShipStatus(ship);
        const navClass = getShipStatusClass(ship);
        const dest = ship.isLive && ship.destination
            ? ship.destination
            : getDestinationPortName(ship.waypoints, ship.progress);

        marker.bindPopup(`
            <div style="min-width:200px;">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${ship.name}</strong>
                    <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'}">${ship.type.label}</span>
                </div>
                <hr style="margin:4px 0;">
                <div style="font-size:0.85rem;">
                    <div>Route: <strong>${routeData[ship.routeIndex]?.name || 'N/A'}</strong></div>
                    <div>Speed: <strong>${(ship.speed * 1000).toFixed(0)}</strong> knots</div>
                    <div>Heading: <strong>${pos.heading.toFixed(0)}\u00b0</strong></div>
                    <div>Destination: <strong>${dest}</strong></div>
                    <div>Status: <span class="${navClass}">\u25cf ${navStatus}</span></div>
                    ${ship.isLive ? '<div><span class="badge bg-success" style="font-size:0.6rem;">LIVE AIS</span></div>' : ''}
                    ${isTracked ? '<div><span class="badge bg-warning text-dark" style="font-size:0.6rem;">TRACKED</span></div>' : ''}
                    ${ship.mmsi ? '<div class="text-muted" style="font-size:0.7rem;">MMSI: ' + ship.mmsi + '</div>' : ''}
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
    document.getElementById('sidebarStatus').textContent =
        `${ships.length} ships tracked` + (usingLiveData ? ' · Live' : '');
}

function animateShips() {
    for (const ship of ships) {
        if (ship.isLive) continue;

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
            const dest = getDestinationPortName(ship.waypoints, ship.progress);
            const navStatus = getShipStatus(ship);
            ship.marker.setPopupContent(`
                <div style="min-width:200px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${ship.name}</strong>
                        <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'}">${ship.type.label}</span>
                    </div>
                    <hr style="margin:4px 0;">
                    <div style="font-size:0.85rem;">
                        <div>Route: <strong>${routeData[ship.routeIndex]?.name || 'N/A'}</strong></div>
                        <div>Speed: <strong>${(ship.speed * 1000).toFixed(0)}</strong> knots</div>
                        <div>Heading: <strong>${pos.heading.toFixed(0)}\u00b0</strong></div>
                        <div>Destination: <strong>${dest}</strong></div>
                        <div>Status: <span class="${getShipStatusClass(ship)}">\u25cf ${navStatus}</span></div>
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

    animationId = requestAnimationFrame(animateShips);
}

function selectShip(id) {
    const ship = ships.find(s => s.id === id);
    if (!ship) return;
    selectedShip = ship;

    let pos;
    if (ship.isLive && ship.realLat !== undefined) {
        pos = { lat: ship.realLat, lng: ship.realLng, heading: ship.heading || 0 };
    } else {
        pos = getWaypointAtProgress(ship.waypoints, ship.progress);
    }
    const panel = document.getElementById('shipPanel');
    panel.style.display = 'block';

    const dest = ship.isLive && ship.destination
        ? ship.destination
        : getDestinationPortName(ship.waypoints, ship.progress);

    document.getElementById('shipPanelName').textContent = ship.name;
    document.getElementById('shipPanelType').textContent = ship.type.label;
    document.getElementById('shipPanelSpeed').textContent = (ship.speed * 1000).toFixed(0) + ' knots';
    document.getElementById('shipPanelHeading').textContent = pos.heading.toFixed(0) + '\u00b0';
    document.getElementById('shipPanelRoute').textContent = routeData[ship.routeIndex]?.name || 'N/A';
    document.getElementById('shipPanelDest').textContent = dest;
    document.getElementById('shipPanelStatus').textContent = '\u25cf ' + getShipStatus(ship);

    const mmsiRow = document.getElementById('shipPanelMmsiRow');
    const sourceRow = document.getElementById('shipPanelSourceRow');
    const untrackBtn = document.getElementById('shipUntrackBtn');

    if (ship.isTracked && ship.mmsi) {
        mmsiRow.style.display = 'flex';
        document.getElementById('shipPanelMmsi').textContent = ship.mmsi;
        sourceRow.style.display = 'flex';
        document.getElementById('shipPanelSource').textContent = ship.data_source || 'AIS';
        untrackBtn.style.display = 'inline-block';
    } else {
        mmsiRow.style.display = 'none';
        sourceRow.style.display = 'none';
        untrackBtn.style.display = 'none';
    }

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
    if (route && route.waypoints) {
        portMap.flyToBounds(L.latLngBounds(route.waypoints.map(w => [w[0], w[1]])), { padding: [50, 50] });
    }
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
        <div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom port-item" style="cursor:pointer;"
             data-lat="${p.latitude}" data-lng="${p.longitude}" data-name="${p.name.replace(/"/g, '&quot;')}">
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
    let filtered;
    if (typeFilter === 'tracked') {
        filtered = ships.filter(s => s.isTracked);
    } else {
        filtered = typeFilter ? ships.filter(s => s.typeKey === typeFilter) : ships;
    }

    document.getElementById('shipCount2').textContent = filtered.length;

    if (!filtered.length) {
        list.innerHTML = '<div class="text-center text-muted py-4">No vessels</div>';
        return;
    }

    list.innerHTML = filtered.map(ship => {
        let pos;
        if (ship.isLive && ship.realLat !== undefined) {
            pos = { lat: ship.realLat, lng: ship.realLng, heading: ship.heading || 0 };
        } else {
            pos = getWaypointAtProgress(ship.waypoints, ship.progress);
        }
        const navStatus = getShipStatus(ship);

        return `
            <div class="d-flex align-items-center justify-content-between py-1 px-2 border-bottom vessel-item" style="cursor:pointer;"
                 onclick="selectShip(${ship.id})">
                <div>
                    <div class="fw-semibold" style="font-size:0.85rem;">
                        <span style="color:${ship.type.color};margin-right:4px;">${ship.type.shape}</span>
                        ${ship.name}
                    </div>
                    <small class="text-muted">
                        ${routeData[ship.routeIndex]?.name || 'N/A'} \u00b7 ${pos.heading.toFixed(0)}\u00b0
                        ${ship.isLive ? '<span class="badge bg-success ms-1" style="font-size:0.6rem;">LIVE</span>' : ''}
                        ${ship.isTracked ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">TRACKED</span>' : ''}
                        ${ship.isTracked && ship.mmsi ? '<br><span style="font-size:0.65rem;">MMSI: ' + ship.mmsi + '</span>' : ''}
                    </small>
                </div>
                <span class="badge bg-${ship.typeKey === 'container' ? 'primary' : ship.typeKey === 'tanker' ? 'danger' : ship.typeKey === 'bulk' ? 'success' : 'info'} rounded-pill" style="font-size:0.7rem;">${ship.type.label}</span>
            </div>
        `;
    }).join('');
}

function searchVesselsLocal(query) {
    const input = document.getElementById('vesselSearchInput');
    const resultsDiv = document.getElementById('vesselSearchResults');
    const statusDiv = document.getElementById('vesselSearchStatus');

    if (!query || query.length < 2) {
        resultsDiv.style.display = 'none';
        statusDiv.style.display = 'none';
        return;
    }

    statusDiv.style.display = 'block';
    statusDiv.textContent = 'Searching...';

    clearTimeout(vesselSearchDebounce);
    vesselSearchDebounce = setTimeout(() => {
        fetch(`/api/portmap/search-vessels?q=${encodeURIComponent(query)}&limit=15`)
            .then(r => r.json())
            .then(data => {
                if (!data.results || data.results.length === 0) {
                    resultsDiv.style.display = 'none';
                    statusDiv.textContent = 'No vessels found';
                    return;
                }

                statusDiv.textContent = `${data.results.length} results`;
                resultsDiv.style.display = 'block';
                resultsDiv.innerHTML = data.results.map(v => {
                    const typeColors = { container: '#0d6efd', tanker: '#dc3545', bulk: '#198754', lng: '#0dcaf0' };
                    const color = typeColors[v.vessel_type] || '#6c757d';
                    const isTracked = trackedVessels.some(t => t.mmsi === v.mmsi);
                    const hasPos = v.latitude && v.longitude;

                    return `
                        <div class="list-group-item list-group-item-action py-2 px-2 vessel-search-item" style="cursor:pointer;border-left:3px solid ${color};"
                            data-mmsi="${v.mmsi}" data-name="${v.name.replace(/"/g, '&quot;')}" data-lat="${v.latitude || ''}" data-lng="${v.longitude || ''}" data-tracked="${isTracked ? '1' : '0'}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex:1;">
                                    <div class="fw-semibold" style="font-size:0.82rem;">${v.name}</div>
                                    <small class="text-muted" style="font-size:0.72rem;">
                                        ${v.mmsi} ${v.flag_country ? '· ' + v.flag_country : ''}
                                    </small>
                                </div>
                                <div class="d-flex gap-1">
                                    ${hasPos && !isTracked ? `<button class="btn btn-sm btn-outline-success py-0 px-1 vessel-track-btn" style="font-size:0.7rem;" data-mmsi="${v.mmsi}" data-name="${v.name.replace(/"/g, '&quot;')}"><i class="bi bi-crosshair"></i> Track</button>` : ''}
                                    ${isTracked ? `<span class="badge bg-success py-0" style="font-size:0.65rem;">TRACKED</span>` : ''}
                                    ${hasPos ? `<button class="btn btn-sm btn-outline-primary py-0 px-1 vessel-goto-btn" style="font-size:0.7rem;" data-lat="${v.latitude}" data-lng="${v.longitude}" data-name="${v.name.replace(/"/g, '&quot;')}"><i class="bi bi-geo-alt"></i></button>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(() => {
                statusDiv.textContent = 'Search failed';
            });
    }, 300);
}

function trackVesselFromSearch(mmsi, name, event) {
    if (event) { event.stopPropagation(); event.preventDefault(); }

    const btn = event ? event.target.closest('button') : null;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    fetch(`/api/portmap/track-vessel/${mmsi}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.vessel) {
                addTrackedVesselToMap(data.vessel);
                searchVesselsLocal(document.getElementById('vesselSearchInput').value);
                renderVesselList();
            }
        })
        .catch(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-crosshair"></i> Track'; }
        });
}

function addTrackedVesselToMap(v) {
    const existing = ships.find(s => s.mmsi === v.mmsi);
    if (existing) {
        if (v.latitude && v.longitude) {
            existing.realLat = parseFloat(v.latitude);
            existing.realLng = parseFloat(v.longitude);
            existing.isLive = true;
            existing.isTracked = true;
            existing.name = v.name;
            existing.speed = v.speed || 0;
            existing.heading = v.heading || 0;
            existing.destination = v.destination || '';
            existing.nav_status = v.nav_status || '';
            existing.data_source = v.data_source || '';
            if (existing.marker) {
                existing.marker.setLatLng([existing.realLat, existing.realLng]);
            }
        }
        return;
    }

    if (!v.latitude || !v.longitude) return;

    const typeKey = v.vessel_type || 'container';
    const typeDef = SHIP_TYPES[typeKey] || SHIP_TYPES.container;

    const ship = {
        id: shipIdCounter++,
        mmsi: v.mmsi,
        imo: v.imo || '',
        name: v.name,
        typeKey: typeKey,
        type: typeDef,
        routeIndex: 0,
        waypoints: [],
        progress: 0,
        speed: v.speed || 0,
        baseSpeed: v.speed || 0,
        isLive: true,
        isTracked: true,
        navstat: 0,
        destination: v.destination || '',
        realLat: parseFloat(v.latitude),
        realLng: parseFloat(v.longitude),
        heading: v.heading || 0,
        flag_country: v.flag_country || '',
        data_source: v.data_source || '',
    };

    ships.push(ship);
    trackedVessels.push({ mmsi: v.mmsi, name: v.name, type: typeKey });
    buildShipMarkers();
    document.getElementById('shipCount2').textContent = ships.length;
}

function flyToVesselPosition(lat, lng, name, event) {
    if (event) { event.stopPropagation(); event.preventDefault(); }
    portMap.flyTo([lat, lng], 8, { duration: 1 });
}

function untrackSelectedShip() {
    if (!selectedShip || !selectedShip.mmsi) return;

    fetch(`/api/portmap/untrack-vessel/${selectedShip.mmsi}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } })
        .then(r => r.json())
        .then(() => {
            trackedVessels = trackedVessels.filter(t => t.mmsi !== selectedShip.mmsi);

            const idx = ships.findIndex(s => s.mmsi === selectedShip.mmsi);
            if (idx !== -1) {
                if (ships[idx].marker) { shipLayer.removeLayer(ships[idx].marker); }
                ships.splice(idx, 1);
            }

            buildShipMarkers();
            renderVesselList();
            closeShipPanel();
        })
        .catch(() => {});
}

function refreshTrackedVessels() {
    if (trackedVessels.length === 0) return;

    const mmsiList = trackedVessels.map(t => t.mmsi);

    mmsiList.forEach(mmsi => {
        fetch(`/api/portmap/vessel-position/${mmsi}`)
            .then(r => r.json())
            .then(data => {
                if (data.found && data.position) {
                    const ship = ships.find(s => s.mmsi === mmsi);
                    if (ship) {
                        ship.realLat = data.position.latitude;
                        ship.realLng = data.position.longitude;
                        ship.speed = data.position.sog || 0;
                        ship.heading = data.position.heading || data.position.cog || 0;
                        ship.destination = data.position.destination || '';
                        ship.nav_status = data.position.nav_status || '';
                        ship.data_source = data.position.data_source || '';
                        ship.isLive = true;

                        if (ship.marker) {
                            ship.marker.setLatLng([ship.realLat, ship.realLng]);
                            const iconEl = ship.marker.getElement();
                            if (iconEl) {
                                const inner = iconEl.querySelector('div');
                                if (inner) inner.style.transform = `rotate(${ship.heading}deg)`;
                            }
                        }
                    }
                }
            })
            .catch(() => {});
    });
}

window.flyToPort = flyToPort;
window.selectShip = selectShip;
window.closeShipPanel = closeShipPanel;
window.toggleFollowShip = toggleFollowShip;
window.highlightShipRoute = highlightShipRoute;
window.untrackSelectedShip = untrackSelectedShip;

(function init() {
    if (typeof L === 'undefined') return setTimeout(init, 50);

    portMap = L.map('portMap', { zoomControl: true }).setView([20, 30], 2);
    window._portMap = portMap;
    L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${MAPTILER_KEY}`, {
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

    document.getElementById('routeCount').textContent = `${routeData.length} routes`;

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
                isLive: false,
                navstat: 0,
            };

            ships.push(ship);
        }
    });

    if (usingLiveData && liveVesselData.length > 0) {
        mergeLiveVessels();
    }

    sidebarEl = document.getElementById('sidebar');
    sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    sidebarOpenBtn = document.getElementById('sidebarOpenBtn');

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
            <button type="button" class="list-group-item list-group-item-action py-1 px-2 port-search-item" style="font-size:0.85rem;cursor:pointer;"
                data-lat="${p.latitude}" data-lng="${p.longitude}" data-name="${p.name.replace(/"/g, '&quot;')}">
                ${p.name} <span class="text-muted">(${p.country})</span>
            </button>
        `).join('');
    });

    document.getElementById('searchResults').addEventListener('click', function (e) {
        const btn = e.target.closest('.port-search-item');
        if (!btn) return;
        const lat = parseFloat(btn.dataset.lat);
        const lng = parseFloat(btn.dataset.lng);
        const name = btn.dataset.name;
        flyToPort(lat, lng, name);
    });

    document.getElementById('tab-ports').addEventListener('shown.bs.tab', function () {
        renderPortList();
    });

    document.getElementById('portTypeFilter2').addEventListener('change', function () {
        renderPortList();
    });

    document.getElementById('portList').addEventListener('click', function (e) {
        const item = e.target.closest('.port-item');
        if (!item || !item.dataset.lat) return;
        flyToPort(parseFloat(item.dataset.lat), parseFloat(item.dataset.lng), item.dataset.name);
    });

    document.getElementById('tab-vessels').addEventListener('shown.bs.tab', function () {
        renderVesselList();
    });

    document.getElementById('vesselTypeFilter').addEventListener('change', function () {
        renderVesselList();
    });

    document.getElementById('vesselSearchInput').addEventListener('input', function () {
        searchVesselsLocal(this.value);
    });

    document.getElementById('vesselSearchBtn').addEventListener('click', function () {
        searchVesselsLocal(document.getElementById('vesselSearchInput').value);
    });

    document.getElementById('vesselSearchResults').addEventListener('click', function (e) {
        const trackBtn = e.target.closest('.vessel-track-btn');
        if (trackBtn) {
            e.stopPropagation();
            trackVesselFromSearch(trackBtn.dataset.mmsi, trackBtn.dataset.name);
            return;
        }
        const gotoBtn = e.target.closest('.vessel-goto-btn');
        if (gotoBtn) {
            e.stopPropagation();
            flyToVesselPosition(parseFloat(gotoBtn.dataset.lat), parseFloat(gotoBtn.dataset.lng), gotoBtn.dataset.name);
            return;
        }
    });

    document.getElementById('refreshVesselsBtn').addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        fetchLiveVessels();
        setTimeout(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        }, 2000);
    });

    document.getElementById('layerRoutes').addEventListener('change', function () {
        if (this.checked) { portMap.addLayer(routeLineLayer); } else { portMap.removeLayer(routeLineLayer); }
    });

    document.getElementById('layerShips').addEventListener('change', function () {
        if (this.checked) { portMap.addLayer(shipLayer); } else { portMap.removeLayer(shipLayer); }
    });

    sidebarToggleBtn.addEventListener('click', function () {
        sidebarHidden = !sidebarHidden;
        if (sidebarHidden) {
            document.body.classList.add('sidebar-collapsed');
            sidebarOpenBtn.style.display = 'block';
            sidebarToggleBtn.querySelector('i').className = 'bi bi-chevron-right';
        } else {
            document.body.classList.remove('sidebar-collapsed');
            sidebarOpenBtn.style.display = 'none';
            sidebarToggleBtn.querySelector('i').className = 'bi bi-chevron-left';
        }
        setTimeout(() => portMap.invalidateSize(), 350);
    });

    sidebarOpenBtn.addEventListener('click', function () {
        sidebarHidden = false;
        document.body.classList.remove('sidebar-collapsed');
        sidebarOpenBtn.style.display = 'none';
        sidebarToggleBtn.querySelector('i').className = 'bi bi-chevron-left';
        setTimeout(() => portMap.invalidateSize(), 350);
    });

    portMap.on('click', function () {
        if (selectedShip) closeShipPanel();
    });

    loadPorts('');
    buildShipMarkers();
    animateShips();

    if (usingLiveData) {
        setInterval(fetchLiveVessels, 60000);
    }

    trackedRefreshInterval = setInterval(refreshTrackedVessels, 30000);

    setTimeout(() => portMap.invalidateSize(), 200);
    window.addEventListener('resize', () => portMap.invalidateSize());

    document.getElementById('sidebarStatus').textContent =
        `${ships.length} ships tracked` + (usingLiveData ? ' · Live' : '');
    document.getElementById('sidebarFooterInfo').textContent =
        `${allPorts.length || '...'} ports · ${ships.length} vessels` +
        (usingLiveData ? ' · LIVE' : '');
})();
