@extends('layouts.app')

@section('title', 'Global Country Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">🌐 Global Country Dashboard</h4>
    <select id="countrySelect" class="form-select" style="width: auto; max-width: 320px;">
        <option value="">Select a country...</option>
        @foreach ($countries as $country)
            <option value="{{ $country->id }}">{{ $country->name }}</option>
        @endforeach
    </select>
</div>

<div id="noSelection" class="text-center py-5">
    <p class="text-muted fs-5">Select a country above to view all intelligence data.</p>
</div>

<div id="countryData" style="display: none;">
    <hr class="my-0 mb-4">

    {{-- Section 1: Economic Indicators --}}
    <div class="row g-3 mb-4" id="statCards">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">GDP (USD B)</div>
                <div class="stat-value" id="dash_gdp">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Inflation (%)</div>
                <div class="stat-value" id="dash_inflation">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Population</div>
                <div class="stat-value" id="dash_population">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Currency</div>
                <div class="stat-value" id="dash_currency">-</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Section 2: Risk Score Engine --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span>Risk Score Engine</span>
                    <span class="badge bg-secondary" id="dash_riskLevelBadge">-</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-auto">
                            <h1 class="mb-0 display-4" id="dash_riskScore">-</h1>
                            <small class="text-muted" id="dash_riskLabel">Waiting...</small>
                        </div>
                        <div class="col">
                            <div class="progress" style="height: 14px;">
                                <div class="progress-bar" id="dash_riskBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 text-center" id="dash_riskComponents">
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Weather</small>
                                <strong id="dash_rWeather">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Inflation</small>
                                <strong id="dash_rInflation">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">News</small>
                                <strong id="dash_rNews">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Currency</small>
                                <strong id="dash_rCurrency">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Current Weather --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Current Weather</div>
                <div class="card-body" id="dash_weatherData">
                    <p class="text-muted mb-0">Select a country to see weather data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3b: Weather Map --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Weather Monitoring Map</span>
            <small class="text-muted" id="dash_weatherMapLabel">Select a country</small>
        </div>
        <div class="card-body p-0">
            <div id="dash_weatherMap" class="map-container" style="height: 400px;"></div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 4: Currency Impact --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Currency Impact Dashboard</span>
                    <div>
                        <label class="me-1 small">Base:</label>
                        <select id="dash_baseCurrency" class="form-select form-select-sm d-inline-block" style="width: auto;">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="JPY">JPY</option>
                            <option value="IDR">IDR</option>
                            <option value="CNY">CNY</option>
                            <option value="SGD">SGD</option>
                            <option value="AUD">AUD</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="dash_currencyChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Exchange Rate Table</div>
                <div class="card-body" id="dash_rateTable">
                    <p class="text-muted mb-0">Loading rates...</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 5: News Intelligence --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>News Intelligence</span>
                    <div class="d-flex gap-2">
                        <input type="text" id="dash_newsKeyword" class="form-control form-control-sm" placeholder="Keyword..." value="logistics trade shipping economy" style="width: 220px;">
                        <button class="btn btn-sm btn-primary" onclick="dash_loadNews()">Search</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Positive</small>
                                <strong class="text-success" id="dash_posCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Neutral</small>
                                <strong class="text-secondary" id="dash_neuCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Negative</small>
                                <strong class="text-danger" id="dash_negCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Overall</small>
                                <strong id="dash_sentimentLabel">-</strong>
                            </div>
                        </div>
                    </div>
                    <div id="dash_newsContainer">
                        <p class="text-muted mb-0">Loading news...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

{{-- Section 6: Port Locations (World Port Index) — always visible --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-geo-alt"></i> Port Locations — World Port Index</span>
            <small class="text-muted" id="dash_portCount">Memuat...</small>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="dash_portSearchName" class="form-control" placeholder="Cari pelabuhan..." oninput="dash_searchPorts()">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-flag"></i></span>
                        <input type="text" id="dash_portSearchCountry" class="form-control" placeholder="Cari negara..." oninput="dash_searchPorts()">
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100" onclick="dash_searchPorts()">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="dash_portMap" class="map-container" style="height: 420px;"></div>
        </div>
        <div class="card-body" id="dash_portList">
            <p class="text-muted mb-0">Memuat data pelabuhan...</p>
        </div>
    </div>

    <hr class="my-4">

<div id="countryDataExtras" style="display: none;">
    {{-- Section 7: Data Visualization --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Economic Profile (Radar)</div>
                <div class="card-body">
                    <canvas id="dash_econRadar" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">GDP · Exports · Imports</div>
                <div class="card-body">
                    <canvas id="dash_tradeChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Risk Component Breakdown</div>
                <div class="card-body">
                    <canvas id="dash_riskPie" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Inflation vs GDP</div>
                <div class="card-body">
                    <canvas id="dash_dualChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 8: Country Comparison --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Country Comparison Engine</span>
            <div>
                <label class="me-1 small">Compare with:</label>
                <select id="dash_compareCountry" class="form-select form-select-sm d-inline-block" style="width: auto;">
                    <option value="">Select...</option>
                    @foreach ($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body" id="dash_compareBody">
            <p class="text-muted mb-0">Select a second country above to compare.</p>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 9: Watchlist --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Favorite Monitoring List</span>
            <button class="btn btn-sm btn-outline-warning" id="dash_watchlistBtn" onclick="dash_toggleWatchlist()">
                <i class="bi bi-star"></i> Add to Watchlist
            </button>
        </div>
        <div class="card-body" id="dash_watchlistContainer">
            <p class="text-muted mb-0">Loading watchlist...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const dash_allCountries = @json($countries);
    let dash_selectedId = null;
    let dash_selectedName = '';
    let dash_countryData = null;

    let dash_weatherMap = null;
    let dash_weatherMarkers = [];
    let dash_portMap = null;
    let dash_portMarkers = [];

    let dash_currencyChart = null;
    let dash_econRadar = null;
    let dash_tradeChart = null;
    let dash_riskPie = null;
    let dash_dualChart = null;

    let dash_mapsInitialized = false;

    function dash_initMaps() {
        if (dash_mapsInitialized) return;
        dash_mapsInitialized = true;

        dash_weatherMap = L.map('dash_weatherMap').setView([20, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(dash_weatherMap);
    }

    function dash_initPortMap() {
        if (dash_portMap) return;
        dash_portMap = L.map('dash_portMap').setView([20, 30], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(dash_portMap);
        dash_loadAllPorts('', '');
    }

    function dash_searchPorts() {
        const name = document.getElementById('dash_portSearchName').value.trim();
        const country = document.getElementById('dash_portSearchCountry').value.trim();
        dash_loadAllPorts(name, country);
    }

    function dash_loadAllPorts(name, country) {
        document.getElementById('dash_portList').innerHTML = '<p class="text-muted mb-0">Memuat data pelabuhan...</p>';

        if (dash_portMarkers.length) {
            dash_portMarkers.forEach(m => dash_portMap.removeLayer(m));
            dash_portMarkers = [];
        }

        const params = new URLSearchParams();
        if (name) params.set('name', name);
        if (country) params.set('country', country);

        const url = '/api/portmap/ports' + (params.toString() ? '?' + params.toString() : '');

        fetch(url)
            .then(r => r.json())
            .then(ports => {
                document.getElementById('dash_portCount').textContent = ports.length + ' ports';

                if (!ports.length) {
                    document.getElementById('dash_portList').innerHTML = '<p class="text-muted mb-0">Tidak ada pelabuhan ditemukan.</p>';
                    return;
                }

                let html = '<div class="table-responsive" style="max-height:240px;overflow-y:auto;"><table class="table table-sm table-hover mb-0"><thead><tr><th>Nama</th><th>Negara</th><th>Tipe</th><th>Lat</th><th>Lon</th></tr></thead><tbody>';
                ports.forEach(p => {
                    const lat = parseFloat(p.latitude);
                    const lon = parseFloat(p.longitude);
                    if (!isNaN(lat) && !isNaN(lon)) {
                        const marker = L.marker([lat, lon]).addTo(dash_portMap)
                            .bindPopup('<b>' + p.name + '</b><br>' + p.country + '<br>' + (p.port_type || 'N/A'));
                        dash_portMarkers.push(marker);
                    }
                    html += '<tr><td>' + p.name + '</td><td>' + p.country + '</td><td>' + (p.port_type || '-') + '</td><td>' + p.latitude + '</td><td>' + p.longitude + '</td></tr>';
                });
                html += '</tbody></table></div>';
                document.getElementById('dash_portList').innerHTML = html;

                if (dash_portMarkers.length > 0) {
                    const group = L.featureGroup(dash_portMarkers);
                    dash_portMap.fitBounds(group.getBounds().pad(0.1));
                }
            })
            .catch(function () {
                document.getElementById('dash_portList').innerHTML = '<p class="text-danger mb-0">Gagal memuat data pelabuhan.</p>';
            });
    }

    function dash_invalidateMaps() {
        setTimeout(function () {
            if (dash_weatherMap) dash_weatherMap.invalidateSize();
            if (dash_portMap) dash_portMap.invalidateSize();
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('dash_baseCurrency').addEventListener('change', function () {
            dash_loadCurrency(this.value);
        });
        dash_loadCurrency('USD');
        dash_initPortMap();
    });

    document.getElementById('countrySelect').addEventListener('change', function () {
        const id = this.value;
        if (!id) {
            document.getElementById('countryData').style.display = 'none';
            document.getElementById('countryDataExtras').style.display = 'none';
            document.getElementById('noSelection').style.display = '';
            return;
        }
        dash_selectedId = parseInt(id);
        dash_selectedName = this.options[this.selectedIndex].text;
        document.getElementById('noSelection').style.display = 'none';
        document.getElementById('countryData').style.display = '';
        document.getElementById('countryDataExtras').style.display = '';
        dash_initMaps();
        dash_invalidateMaps();
        dash_loadCountry(dash_selectedId);
    });

    document.getElementById('dash_compareCountry').addEventListener('change', function () {
        dash_renderComparison();
    });

    // ===================== LOAD ALL DATA (with instant DB data) =====================
    function dash_loadCountry(id) {
        const dbCountry = dash_allCountries.find(c => c.id === id);
        if (dbCountry) {
            dash_countryData = dbCountry;
            dash_renderStats(dbCountry);
            dash_renderCharts(dbCountry);
        }

        document.getElementById('dash_riskScore').textContent = 'Loading...';

        fetch(`/api/countries/${id}`)
            .then(r => r.json())
            .then(country => {
                dash_countryData = country;
                dash_renderStats(country);
                dash_renderWeather(country.weather);
                dash_renderCharts(country);

                const name = country.name;
                dash_fetchWeatherMap(name);
                dash_loadNews(name);
                dash_updateWatchlistBtn();
            })
            .catch(() => {});

        fetch(`/api/risk?country_id=${id}`)
            .then(r => r.json())
            .then(risks => {
                dash_renderRisk(dash_countryData, risks);
            })
            .catch(() => {
                document.getElementById('dash_riskScore').textContent = 'ERR';
                document.getElementById('dash_riskLabel').textContent = 'Failed to load risk';
            });
    }

    // ===================== SECTION 1: STATS =====================
    function dash_renderStats(c) {
        document.getElementById('dash_gdp').textContent = c.gdp?.toLocaleString() ?? '-';
        document.getElementById('dash_inflation').textContent = c.inflation != null ? c.inflation + '%' : '-';
        document.getElementById('dash_population').textContent = c.population?.toLocaleString() ?? '-';
        document.getElementById('dash_currency').textContent = c.currency_code ?? '-';
    }

    // ===================== SECTION 2: RISK =====================
    function dash_renderRisk(country, risks) {
        const risk = Array.isArray(risks) && risks.length > 0 ? risks[0] : null;
        if (!risk) {
            document.getElementById('dash_riskScore').textContent = '-';
            document.getElementById('dash_riskBar').style.width = '0%';
            document.getElementById('dash_riskLabel').textContent = 'No data';
            document.getElementById('dash_riskLevelBadge').textContent = '-';
            return;
        }

        const score = risk.total_score;
        document.getElementById('dash_riskScore').textContent = score;
        document.getElementById('dash_riskBar').style.width = Math.min(score, 100) + '%';
        const color = score <= 30 ? 'success' : score <= 60 ? 'warning' : 'danger';
        document.getElementById('dash_riskBar').className = 'progress-bar bg-' + color;
        const level = (risk.risk_level || '').toUpperCase();
        document.getElementById('dash_riskLabel').textContent = `${score} — ${level}`;
        document.getElementById('dash_riskLevelBadge').textContent = level;
        document.getElementById('dash_riskLevelBadge').className = 'badge bg-' + color;

        document.getElementById('dash_rWeather').textContent = Math.round(risk.weather_risk || 0);
        document.getElementById('dash_rInflation').textContent = Math.round(risk.inflation_risk || 0);
        document.getElementById('dash_rNews').textContent = Math.round(risk.news_sentiment_risk || 0);
        document.getElementById('dash_rCurrency').textContent = Math.round(risk.currency_risk || 0);
    }

    // ===================== SECTION 3: WEATHER =====================
    function dash_renderWeather(w) {
        const wd = document.getElementById('dash_weatherData');
        if (w && w.temperature_2m != null) {
            const icon = w.weather_code <= 2 ? '☀️' : w.weather_code <= 5 ? '⛅' : w.weather_code <= 50 ? '🌧️' : '⛈️';
            wd.innerHTML = `
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fs-1">${icon}</div>
                        <strong>${w.temperature_2m ?? '-'}°C</strong>
                        <small class="text-muted d-block">Temperature</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-1">🌧</div>
                        <strong>${w.precipitation ?? 0} mm</strong>
                        <small class="text-muted d-block">Precipitation</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-1">💨</div>
                        <strong>${w.wind_speed_10m ?? '-'} km/h</strong>
                        <small class="text-muted d-block">Wind Speed</small>
                    </div>
                </div>`;
        } else {
            wd.innerHTML = '<p class="text-muted mb-0">Weather data unavailable.</p>';
        }
    }

    function dash_fetchWeatherMap(countryName) {
        document.getElementById('dash_weatherMapLabel').textContent = `Loading weather for ${countryName}...`;

        dash_weatherMarkers.forEach(m => dash_weatherMap.removeLayer(m));
        dash_weatherMarkers = [];

        fetch(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(countryName)}&count=3&language=en&format=json`)
            .then(r => r.json())
            .then(geo => {
                if (!geo.results?.length) {
                    document.getElementById('dash_weatherMapLabel').textContent = 'No weather data found.';
                    return;
                }
                const promises = geo.results.map(loc => {
                    const lat = parseFloat(loc.latitude);
                    const lon = parseFloat(loc.longitude);
                    return fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,precipitation,wind_speed_10m,weather_code&timezone=auto`)
                        .then(r => r.json())
                        .then(data => ({ loc, data }));
                });

                return Promise.all(promises);
            })
            .then(results => {
                if (!results) return;
                dash_weatherMarkers.forEach(m => dash_weatherMap.removeLayer(m));
                dash_weatherMarkers = [];

                results.forEach(({ loc, data }) => {
                    const c = data.current || {};
                    const icon = c.weather_code <= 2 ? '☀️' : c.weather_code <= 5 ? '⛅' : c.weather_code <= 50 ? '🌧️' : '⛈️';
                    const popup = `
                        <b>${loc.name}${loc.country ? ', ' + loc.country : ''}</b><br>
                        ${icon} ${c.temperature_2m ?? '-'}°C<br>
                        🌧 ${c.precipitation ?? 0} mm<br>
                        💨 ${c.wind_speed_10m ?? '-'} km/h`;

                    const marker = L.marker([loc.latitude, loc.longitude]).addTo(dash_weatherMap)
                        .bindPopup(popup);
                    dash_weatherMarkers.push(marker);
                });

                if (dash_weatherMarkers.length > 0) {
                    const group = L.featureGroup(dash_weatherMarkers);
                    dash_weatherMap.fitBounds(group.getBounds().pad(0.3));
                }
                document.getElementById('dash_weatherMapLabel').textContent = `Weather in ${countryName}`;
            })
            .catch(() => {
                document.getElementById('dash_weatherMapLabel').textContent = 'Weather map data unavailable.';
            });
    }

    // ===================== SECTION 4: CURRENCY =====================
    function dash_loadCurrency(base) {
        fetch(`/api/currency?base=${base}`)
            .then(r => r.json())
            .then(rates => {
                const currencies = ['EUR', 'GBP', 'JPY', 'CNY', 'IDR', 'AUD', 'SGD', 'MYR'];
                const values = currencies.map(c => rates[c] ?? 0);

                let html = '<table class="table table-sm mb-0">';
                currencies.forEach((c, i) => {
                    html += `<tr><td>${c}</td><td class="text-end fw-bold">${values[i].toFixed(4)}</td></tr>`;
                });
                html += '</table>';
                document.getElementById('dash_rateTable').innerHTML = html;

                if (dash_currencyChart) dash_currencyChart.destroy();
                const ctx = document.getElementById('dash_currencyChart').getContext('2d');
                dash_currencyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: currencies,
                        datasets: [{
                            label: `1 ${base} to`,
                            data: values.map(v => Math.min(v, 1000)),
                            backgroundColor: '#0d6efd'
                        }]
                    },
                    options: {
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        const actual = values[ctx.dataIndex];
                                        return `1 ${base} = ${actual.toFixed(4)} ${currencies[ctx.dataIndex]}`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
    }

    // ===================== SECTION 5: NEWS =====================
    function dash_loadNews() {
        const country = dash_countryData;
        if (!country) return;

        const keyword = document.getElementById('dash_newsKeyword').value || 'logistics trade shipping economy';
        const container = document.getElementById('dash_newsContainer');
        container.innerHTML = '<p class="text-muted mb-0">Loading news...</p>';

        const url = `/api/news?country_id=${country.id}&keyword=${encodeURIComponent(keyword)}`;

        fetch(url)
            .then(r => r.json())
            .then(news => {
                const items = Array.isArray(news) ? news : (news.data || []);
                if (items.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0">No news found for this country. Try a different keyword.</p>';
                    return;
                }
                dash_renderNewsItems(items);
            })
            .catch(() => {
                container.innerHTML = '<p class="text-danger mb-0">Failed to load news. Check API key.</p>';
            });
    }

    function dash_renderNewsItems(items) {
        const container = document.getElementById('dash_newsContainer');
        let pos = 0, neg = 0, neu = 0;
        let html = '<div class="list-group">';
        items.forEach(item => {
            const title = item.title || '(No title)';
            const desc = item.description || '';
            const source = item.source || '';
            const url = item.url || '#';
            const date = item.published_at ? new Date(item.published_at).toLocaleDateString() : '';
            const sentiment = (item.sentiment || 'neutral').toLowerCase();
            if (sentiment === 'positive') pos++;
            else if (sentiment === 'negative') neg++;
            else neu++;

            html += `<a href="${url}" target="_blank" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <strong>${title}</strong>
                    <small class="text-muted">${date}</small>
                </div>
                <p class="mb-1 text-muted small">${desc}</p>
                <small class="text-${sentiment === 'positive' ? 'success' : sentiment === 'negative' ? 'danger' : 'secondary'}">${sentiment}</small>
                ${source ? ` <small class="text-muted">— ${source}</small>` : ''}
            </a>`;
        });
        html += '</div>';
        container.innerHTML = html;

        document.getElementById('dash_posCount').textContent = pos;
        document.getElementById('dash_neuCount').textContent = neu;
        document.getElementById('dash_negCount').textContent = neg;
        const overall = pos > neg ? 'Positive' : neg > pos ? 'Negative' : 'Neutral';
        const cls = pos > neg ? 'text-success' : neg > pos ? 'text-danger' : 'text-secondary';
        const sl = document.getElementById('dash_sentimentLabel');
        sl.textContent = overall;
        sl.className = cls;
    }

    // ===================== SECTION 7: CHARTS =====================
    function dash_renderCharts(c) {
        // Radar: Economic Profile
        const radarLabels = ['GDP', 'Inflation', 'Population', 'Exports', 'Imports'];
        const radarData = [c.gdp || 0, c.inflation || 0, c.population || 0, c.exports || 0, c.imports || 0];
        if (dash_econRadar) dash_econRadar.destroy();
        dash_econRadar = new Chart(document.getElementById('dash_econRadar'), {
            type: 'radar',
            data: {
                labels: radarLabels,
                datasets: [{
                    label: c.name,
                    data: radarData,
                    backgroundColor: 'rgba(13,110,253,0.2)',
                    borderColor: '#0d6efd',
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Bar: GDP, Exports, Imports
        if (dash_tradeChart) dash_tradeChart.destroy();
        dash_tradeChart = new Chart(document.getElementById('dash_tradeChart'), {
            type: 'bar',
            data: {
                labels: ['GDP', 'Exports', 'Imports'],
                datasets: [{
                    label: 'USD (B)',
                    data: [c.gdp || 0, c.exports || 0, c.imports || 0],
                    backgroundColor: ['#0d6efd', '#198754', '#dc3545']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Pie: Risk Components
        if (dash_riskPie) dash_riskPie.destroy();
        dash_riskPie = new Chart(document.getElementById('dash_riskPie'), {
            type: 'doughnut',
            data: {
                labels: ['Weather 30%', 'Inflation 20%', 'News 40%', 'Currency 10%'],
                datasets: [{
                    data: [30, 20, 40, 10],
                    backgroundColor: ['#0dcaf0', '#ffc107', '#0d6efd', '#198754']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Bar: Inflation vs GDP (normalized)
        if (dash_dualChart) dash_dualChart.destroy();
        const maxGdp = Math.max(...dash_allCountries.map(x => x.gdp || 0), 1);
        const gdpNorm = ((c.gdp || 0) / maxGdp * 100).toFixed(1);
        dash_dualChart = new Chart(document.getElementById('dash_dualChart'), {
            type: 'bar',
            data: {
                labels: ['Inflation (%)', 'GDP (normalized)'],
                datasets: [{
                    label: c.name,
                    data: [c.inflation || 0, parseFloat(gdpNorm)],
                    backgroundColor: ['#ffc107', '#0d6efd']
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // ===================== SECTION 8: COMPARISON =====================
    function dash_renderComparison() {
        const compareId = parseInt(document.getElementById('dash_compareCountry').value);
        const body = document.getElementById('dash_compareBody');

        if (!compareId || !dash_countryData) {
            body.innerHTML = '<p class="text-muted mb-0">Select a second country above to compare.</p>';
            return;
        }

        const a = dash_countryData;
        const b = dash_allCountries.find(c => c.id === compareId);
        if (!b) return;

        const indicators = [
            { label: 'GDP (USD B)', key: 'gdp', higher: true, fmt: v => v?.toLocaleString() ?? '-' },
            { label: 'Inflation (%)', key: 'inflation', higher: false, fmt: v => v != null ? v + '%' : '-' },
            { label: 'Population', key: 'population', higher: true, fmt: v => v?.toLocaleString() ?? '-' },
            { label: 'Exports (USD B)', key: 'exports', higher: true, fmt: v => v?.toLocaleString() ?? '-' },
            { label: 'Imports (USD B)', key: 'imports', higher: true, fmt: v => v?.toLocaleString() ?? '-' },
        ];

        let html = `<div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width:25%">Indicator</th>
                        <th style="width:25%" class="text-center">${a.name}</th>
                        <th style="width:25%" class="text-center">${b.name}</th>
                        <th style="width:25%" class="text-center">Winner</th>
                    </tr>
                </thead>
                <tbody>`;

        indicators.forEach(ind => {
            const va = a[ind.key] ?? 0;
            const vb = b[ind.key] ?? 0;
            const winner = va > vb ? a.name : vb > va ? b.name : 'Draw';
            html += `<tr>
                <td>${ind.label}</td>
                <td class="fw-bold text-center ${va > vb ? 'text-success' : ''}">${ind.fmt(a[ind.key])}</td>
                <td class="fw-bold text-center ${vb > va ? 'text-success' : ''}">${ind.fmt(b[ind.key])}</td>
                <td class="text-center"><span class="badge bg-${winner === 'Draw' ? 'secondary' : 'primary'}">${winner}</span></td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        html += `<div class="mt-3"><canvas id="dash_compRadar" height="200"></canvas></div>`;
        body.innerHTML = html;

        const ctx = document.getElementById('dash_compRadar').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: indicators.map(i => i.label.split('(')[0].trim()),
                datasets: [
                    { label: a.name, data: indicators.map(i => a[i.key] || 0), backgroundColor: 'rgba(13,110,253,0.15)', borderColor: '#0d6efd', pointBackgroundColor: '#0d6efd' },
                    { label: b.name, data: indicators.map(i => b[i.key] || 0), backgroundColor: 'rgba(220,53,69,0.15)', borderColor: '#dc3545', pointBackgroundColor: '#dc3545' },
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // ===================== SECTION 9: WATCHLIST =====================
    function dash_getWatchlist() {
        return JSON.parse(localStorage.getItem('watchlist') || '[]');
    }

    function dash_saveWatchlist(ids) {
        localStorage.setItem('watchlist', JSON.stringify(ids));
    }

    function dash_toggleWatchlist() {
        if (!dash_selectedId) return;
        let ids = dash_getWatchlist();
        const idx = ids.indexOf(dash_selectedId);
        if (idx > -1) {
            ids.splice(idx, 1);
        } else {
            ids.push(dash_selectedId);
        }
        dash_saveWatchlist(ids);
        dash_updateWatchlistBtn();
        dash_renderWatchlist();
    }

    function dash_updateWatchlistBtn() {
        if (!dash_selectedId) return;
        const ids = dash_getWatchlist();
        const btn = document.getElementById('dash_watchlistBtn');
        if (ids.includes(dash_selectedId)) {
            btn.innerHTML = '<i class="bi bi-star-fill text-warning"></i> Remove from Watchlist';
            btn.className = 'btn btn-sm btn-outline-danger';
        } else {
            btn.innerHTML = '<i class="bi bi-star"></i> Add to Watchlist';
            btn.className = 'btn btn-sm btn-outline-warning';
        }
    }

    function dash_renderWatchlist() {
        const ids = dash_getWatchlist();
        const container = document.getElementById('dash_watchlistContainer');

        if (ids.length === 0) {
            container.innerHTML = '<p class="text-muted mb-0">No countries in your watchlist. Add the current country using the button above!</p>';
            return;
        }

        const watched = dash_allCountries.filter(c => ids.includes(c.id));
        let html = '<div class="row g-2">';
        watched.forEach(c => {
            html += `<div class="col-md-4 col-lg-3">
                <div class="card">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="small">${c.name}</strong>
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="dash_removeWatchlist(${c.id})">&times;</button>
                        </div>
                        <small class="text-muted">GDP: ${c.gdp?.toLocaleString() ?? '-'}B | Inf: ${c.inflation ?? '-'}%</small>
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function dash_removeWatchlist(id) {
        let ids = dash_getWatchlist();
        ids = ids.filter(i => i !== id);
        dash_saveWatchlist(ids);
        dash_updateWatchlistBtn();
        dash_renderWatchlist();
    }

    document.addEventListener('DOMContentLoaded', dash_renderWatchlist);
</script>
@endsection
