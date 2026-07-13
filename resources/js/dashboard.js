const dash_allCountries = window.__COUNTRIES;
let dash_selectedId = null;
let dash_selectedName = '';
let dash_countryData = null;

let dash_map = null;
let dash_mapMarkers = [];

let dash_currencyChart = null;
let dash_econRadar = null;
let dash_tradeChart = null;
let dash_riskPie = null;
let dash_dualChart = null;

function dash_initMap() {
    if (dash_map) return;
    dash_map = L.map('dash_countryMap', {
        zoomControl: true,
        attributionControl: true,
    }).setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(dash_map);
}

function dash_invalidateMaps() {
    setTimeout(function () {
        if (dash_map) dash_map.invalidateSize();
    }, 150);
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('dash_baseCurrency').addEventListener('change', function () {
        dash_loadCurrency(this.value);
    });
    dash_loadCurrency('USD');
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
    dash_initMap();
    dash_invalidateMaps();
    dash_loadCountry(dash_selectedId);
});

document.getElementById('dash_compareCountry').addEventListener('change', function () {
    dash_renderComparison();
});

function dash_loadCountry(id) {
    const dbCountry = dash_allCountries.find(c => c.id === id);
    if (dbCountry) {
        dash_countryData = dbCountry;
        dash_renderStats(dbCountry);
        dash_renderCharts(dbCountry);
    }

    document.getElementById('dash_gdp').textContent = '';
    document.getElementById('dash_inflation').textContent = '';
    document.getElementById('dash_population').textContent = '';
    document.getElementById('dash_currency').textContent = '';
    document.getElementById('dash_gdpLoader').classList.add('active');
    document.getElementById('dash_inflationLoader').classList.add('active');
    document.getElementById('dash_populationLoader').classList.add('active');
    document.getElementById('dash_currencyLoader').classList.add('active');
    document.getElementById('dash_riskScore').textContent = 'Loading...';
    document.getElementById('dash_riskLabel').textContent = 'Waiting...';
    document.getElementById('dash_riskBar').style.width = '0%';
    document.getElementById('dash_riskLevelBadge').textContent = '-';
    document.getElementById('dash_riskLevelBadge').className = 'badge bg-secondary';
    document.getElementById('dash_rWeather').textContent = '-';
    document.getElementById('dash_rInflation').textContent = '-';
    document.getElementById('dash_rNews').textContent = '-';
    document.getElementById('dash_rCurrency').textContent = '-';
    document.getElementById('dash_weatherData').innerHTML = '<p class="text-muted mb-0">Loading weather data...</p>';

    fetch(`/api/countries/${id}`)
        .then(r => r.json())
        .then(country => {
            dash_countryData = country;
            dash_renderStats(country);
            dash_renderWeather(country.weather);
            dash_renderCharts(country);

            const name = country.name;
            dash_fetchMapData(country.name, country.iso_code);
            dash_loadNews(name);
            dash_updateWatchlistBtn();
        })
        .catch(() => {
            document.getElementById('dash_weatherData').innerHTML = '<p class="text-danger mb-0">Failed to load weather data.</p>';
        });

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

function dash_renderStats(c) {
    document.getElementById('dash_gdp').textContent = c.gdp?.toLocaleString() ?? '-';
    document.getElementById('dash_inflation').textContent = c.inflation != null ? c.inflation + '%' : '-';
    document.getElementById('dash_population').textContent = c.population?.toLocaleString() ?? '-';
    document.getElementById('dash_currency').textContent = c.currency_code ?? '-';
    document.getElementById('dash_gdpLoader').classList.remove('active');
    document.getElementById('dash_inflationLoader').classList.remove('active');
    document.getElementById('dash_populationLoader').classList.remove('active');
    document.getElementById('dash_currencyLoader').classList.remove('active');
}

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
    document.getElementById('dash_riskScore').textContent = score + '%';
    document.getElementById('dash_riskBar').style.width = Math.min(score, 100) + '%';
    const color = score <= 30 ? 'success' : score <= 60 ? 'warning' : 'danger';
    document.getElementById('dash_riskBar').className = 'progress-bar bg-' + color;
    const level = (risk.risk_level || '').toUpperCase();
    document.getElementById('dash_riskLabel').textContent = `${score}% — ${level}`;
    document.getElementById('dash_riskLevelBadge').textContent = level;
    document.getElementById('dash_riskLevelBadge').className = 'badge bg-' + color;

    document.getElementById('dash_rWeather').textContent = Math.round(risk.weather_risk || 0);
    document.getElementById('dash_rInflation').textContent = Math.round(risk.inflation_risk || 0);
    document.getElementById('dash_rNews').textContent = Math.round(risk.news_sentiment_risk || 0);
    document.getElementById('dash_rCurrency').textContent = Math.round(risk.currency_risk || 0);
}

function dash_renderWeather(w) {
    const wd = document.getElementById('dash_weatherData');
    if (w && w.temperature_2m != null) {
        const icon = w.weather_code <= 2 ? '☀️' : w.weather_code <= 5 ? '⛅' : w.weather_code <= 50 ? '🌧️' : '⛈️';
        const stormRisk = dash_stormRisk(w.weather_code ?? 0, w.wind_speed_10m ?? 0);
        const stormColor = stormRisk >= 60 ? 'danger' : stormRisk >= 30 ? 'warning' : 'success';
        wd.innerHTML = `
            <div class="row text-center">
                <div class="col-3">
                    <div class="fs-1">${icon}</div>
                    <strong>${w.temperature_2m ?? '-'}°C</strong>
                    <small class="text-muted d-block">Temperature</small>
                </div>
                <div class="col-3">
                    <div class="fs-1">🌧</div>
                    <strong>${w.precipitation ?? 0} mm</strong>
                    <small class="text-muted d-block">Precipitation</small>
                </div>
                <div class="col-3">
                    <div class="fs-1">💨</div>
                    <strong>${w.wind_speed_10m ?? '-'} km/h</strong>
                    <small class="text-muted d-block">Wind</small>
                </div>
                <div class="col-3">
                    <div class="fs-1">⛈️</div>
                    <span class="badge bg-${stormColor}">${stormRisk >= 60 ? 'High' : stormRisk >= 30 ? 'Mod' : 'Low'}</span>
                    <small class="text-muted d-block">Storm Risk</small>
                </div>
            </div>`;
    } else {
        wd.innerHTML = '<p class="text-muted mb-0">Weather data unavailable.</p>';
    }
}

function dash_stormRisk(code, wind) {
    const c = parseInt(code);
    const w = parseFloat(wind);
    let s = 0;
    if (c >= 96) s = 95; else if (c >= 95) s = 85; else if (c >= 86) s = 70; else if (c >= 80) s = 55; else if (c >= 71) s = 50;
    let ws = 0;
    if (w >= 70) ws = 90; else if (w >= 50) ws = 70; else if (w >= 38) ws = 50; else if (w >= 25) ws = 25;
    return Math.min(100, Math.max(s, ws));
}

var dash_countryCoords = {
    AF:[33.9391,67.7100],DE:[51.1657,10.4515],CN:[35.8617,104.1954],ID:[-0.7893,113.9213],AU:[-25.2744,133.7751],
    US:[37.0902,-95.7129],JP:[36.2048,138.2529],SG:[1.3521,103.8198],MY:[4.2105,101.9753],
    GB:[55.3781,-3.4360],IN:[20.5937,78.9629],TH:[15.8700,100.9925],VN:[14.0583,108.2772],
    PH:[12.8797,121.7740],KR:[35.9078,127.7669],TW:[23.6978,120.9605],HK:[22.3193,114.1694],
    NL:[52.1326,5.2913],BE:[50.8503,4.3517],FR:[46.2276,2.2137],ES:[40.4637,-3.7492],
    IT:[41.8719,12.5674],PT:[39.3999,-8.2245],SE:[60.1282,18.6435],PL:[51.9194,19.1451],
    RO:[45.9432,24.9668],GR:[39.0742,21.8243],TR:[38.9637,35.2433],RU:[61.5240,105.3188],
    UA:[48.3794,31.1656],CA:[56.1304,-106.3468],BR:[-14.2350,-51.9253],AR:[-38.4161,-63.6167],
    CL:[-35.6751,-71.5430],CO:[4.5709,-74.2973],PE:[-9.1900,-75.0152],PA:[8.5380,-80.7821],
    MX:[23.6345,-102.5528],ZA:[-30.5595,22.9375],NG:[9.0820,8.6753],EG:[26.8206,30.8025],
    KE:[-0.0236,37.9062],TZ:[-6.3690,34.8888],GH:[7.9465,-1.0232],CI:[7.5400,-5.5471],
    MA:[31.7917,-7.0926],AE:[23.4241,53.8478],SA:[23.8859,45.0792],OM:[21.4735,55.9754],
    QA:[25.3548,51.1839],KW:[29.3117,47.4818],IQ:[33.2232,43.6793],IR:[32.4279,53.6880],
    PK:[30.3753,69.3451],BD:[23.6850,90.3563],MM:[21.9162,95.9560],LK:[7.8731,80.7718],
    FJ:[-17.7134,178.0650],DZ:[28.0339,1.6596],TN:[33.8869,9.5375],LY:[26.3351,17.2283],
    LB:[33.8547,35.8623],IL:[31.0461,34.8516],JO:[30.5852,36.2384],SY:[34.8021,38.9968],
    BH:[26.0667,50.5577],FK:[-51.7963,-59.5236],CK:[-21.2098,-159.7804],TO:[-21.1790,-175.1982],
    WS:[-13.7590,-172.1046],PG:[-6.3150,143.9555],MN:[46.8625,103.8467],
    KH:[12.5657,104.9910],LA:[19.8567,102.4955],NP:[28.3949,84.1240],
    BT:[27.5142,90.4336],MV:[3.2028,73.2207],BN:[4.5353,114.7277],
    TL:[-8.8742,125.7275],MG:[-18.7669,46.8691],MU:[-20.3484,57.5522],SC:[-4.6796,55.4920],
    MZ:[-18.6657,35.5296],AO:[-11.2027,17.8739],CM:[7.3697,12.3547],GA:[-0.8037,11.6094],
    SN:[14.4974,-14.4524],GN:[9.9456,-9.6966],SL:[8.4606,-11.7799],LR:[6.4281,-9.4295],
    CF:[6.6111,20.9394],TD:[15.4542,18.7322],NE:[17.6078,8.0817],ML:[17.5707,-3.9961],
    BF:[12.3714,-1.5197],ZM:[-13.1339,28.6387],ZW:[-19.0154,29.1549],
    BW:[-22.3285,24.6849],NA:[-22.9576,18.4904],SZ:[-26.5225,31.4659],LS:[-29.6100,28.2336],
    UG:[1.3733,32.2903],RW:[-1.9403,29.8739],BI:[-3.3731,29.9189],ET:[9.1450,40.4897],
    SO:[5.1521,46.1996],DJ:[11.8251,42.5903],ER:[15.1794,39.7823],SS:[6.8770,31.3070],
    SD:[12.8628,30.2176],CG:[-0.2280,15.8277],GQ:[1.6508,10.2679],
    ST:[0.1864,6.6131],CV:[16.5388,-23.0418],GM:[13.4432,-15.3101],
    GW:[11.8037,-15.1804],TG:[8.6195,1.2080],BJ:[9.3077,2.3158],
    MR:[21.0079,-10.9408],KM:[-11.6455,43.3333],MW:[-13.2543,34.3015],
    CU:[21.5218,-77.7812],JM:[18.1096,-77.2975],BS:[25.0343,-77.3963],BB:[13.1939,-59.5432],
    TT:[10.6918,-61.2225],GY:[4.8604,-58.9302],SR:[3.9193,-56.0278],HT:[18.9712,-72.2852],
    DO:[18.7357,-70.1627],PR:[18.2208,-66.5901],VE:[6.4238,-66.5897],EC:[-1.8312,-78.1834],
    BO:[-16.2902,-63.5887],PY:[-23.4425,-58.4438],UY:[-32.5228,-55.7658],GF:[4.9429,-52.2330],
    GI:[36.1408,-5.3536],AD:[42.5063,1.5218],MC:[43.7384,7.4246],
    LI:[47.1660,9.5554],SM:[43.9424,12.4578],VA:[41.9029,12.4534],MT:[35.9375,14.3754],
    CY:[35.1264,33.4299],IS:[64.9631,-19.0208],NO:[60.4720,8.4689],FI:[61.9241,25.7482],
    DK:[56.2639,9.5018],EE:[58.5953,25.0136],LV:[56.8796,24.6032],LT:[55.1694,23.8813],
    BY:[53.7098,27.9534],MD:[47.4116,28.3699],AL:[41.1533,20.1683],BA:[43.9159,17.6791],
    ME:[42.7087,19.3744],RS:[44.0165,21.0059],MK:[41.5122,21.7453],XK:[42.6026,20.9020],
    AT:[47.5162,14.5501],IE:[53.1424,-7.6921],KZ:[48.0196,66.9237],NZ:[-40.9006,174.8860],
    AQ:[-82.8628,135.0]
};

function dash_fetchMapData(countryName, countryCode) {
    document.getElementById('dash_mapLabel').textContent = `Loading map for ${countryName}...`;
    document.getElementById('dash_mapLoader').classList.remove('hidden');

    dash_mapMarkers.forEach(m => dash_map.removeLayer(m));
    dash_mapMarkers = [];

    var coords = dash_countryCoords[countryCode] || null;

    const portsPromise = fetch(`/api/portmap/ports?country=${encodeURIComponent(countryName)}`)
        .then(r => r.json())
        .catch(() => []);

    portsPromise.then(ports => {
        if (!coords && !ports.length) {
            document.getElementById('dash_mapLabel').textContent = 'No data found.';
            document.getElementById('dash_mapLoader').classList.add('hidden');
            return;
        }

        var allMarkers = [];

        if (coords) {
            const countryIcon = L.divIcon({
                className: '',
                html: '<div style="font-size:30px;line-height:1;text-align:center;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.4));">📍</div>',
                iconSize: [30, 36],
                iconAnchor: [15, 36],
                popupAnchor: [0, -38],
            });

            const countryMarker = L.marker(coords, { icon: countryIcon })
                .addTo(dash_map)
                .bindPopup(`<strong>📍 ${countryName}</strong>`, { maxWidth: 200 });
            allMarkers.push(countryMarker);
        }

        if (ports.length > 0) {
            const portIcon = L.divIcon({
                className: '',
                html: '<div style="font-size:22px;line-height:1;text-align:center;filter:drop-shadow(0 1px 4px rgba(0,0,0,0.35));">⚓</div>',
                iconSize: [22, 26],
                iconAnchor: [11, 26],
                popupAnchor: [0, -28],
            });

            ports.forEach(p => {
                const pm = L.marker([p.latitude, p.longitude], { icon: portIcon })
                    .addTo(dash_map)
                    .bindPopup(`<strong>⚓ ${p.name}</strong><br><small>${p.country} &middot; ${p.port_type || 'N/A'}</small>`, { maxWidth: 200 });
                allMarkers.push(pm);
            });
        }

        if (allMarkers.length > 0) {
            dash_mapMarkers = allMarkers;
            const group = L.featureGroup(allMarkers);
            dash_map.fitBounds(group.getBounds().pad(0.3));
        }

        const portLabel = ports.length > 0 ? ` · ${ports.length} ports` : '';
        document.getElementById('dash_mapLabel').textContent = `${countryName}${portLabel}`;
        document.getElementById('dash_mapLoader').classList.add('hidden');
        setTimeout(() => dash_map.invalidateSize(), 100);
    }).catch(() => {
        document.getElementById('dash_mapLabel').textContent = 'Map data unavailable.';
        document.getElementById('dash_mapLoader').classList.add('hidden');
    });
}

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

document.querySelectorAll('#dash_newsCats button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#dash_newsCats button').forEach(b => b.classList.remove('active', 'btn-secondary'));
        btn.classList.add('active', 'btn-secondary');
        document.getElementById('dash_newsKeyword').value = btn.dataset.q;
        dash_loadNews();
    });
});

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

function dash_renderCharts(c) {
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

    if (dash_riskPie) dash_riskPie.destroy();
    dash_riskPie = new Chart(document.getElementById('dash_riskPie'), {
        type: 'doughnut',
        data: {
            labels: ['Weather 25%', 'Inflation 25%', 'News 25%', 'FX Rate 25%'],
            datasets: [{
                data: [30, 20, 40, 10],
                backgroundColor: ['#0dcaf0', '#ffc107', '#0d6efd', '#198754']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

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

async function dash_renderComparison() {
    const compareId = parseInt(document.getElementById('dash_compareCountry').value);
    const body = document.getElementById('dash_compareBody');

    if (!compareId || !dash_countryData) {
        body.innerHTML = '<p class="text-muted mb-0">Select a second country above to compare.</p>';
        return;
    }

    const a = dash_countryData;
    body.innerHTML = '<p class="text-muted mb-0">Loading comparison...</p>';

    let bData, aRisk, bRisk;
    try {
        [aRisk, bRisk, bData] = await Promise.all([
            fetch(`/api/risk?country_id=${a.id}`).then(r => r.json()),
            fetch(`/api/risk?country_id=${compareId}`).then(r => r.json()),
            fetch(`/api/countries/${compareId}`).then(r => r.json()),
        ]);
    } catch (e) {
        body.innerHTML = '<p class="text-danger mb-0">Failed to load comparison data.</p>';
        return;
    }

    const aRiskScore = Array.isArray(aRisk) && aRisk.length ? aRisk[0].total_score : '-';
    const bRiskScore = Array.isArray(bRisk) && bRisk.length ? bRisk[0].total_score : '-';
    const aWeather = a.weather?.temperature_2m ?? '-';
    const bWeather = bData.weather?.temperature_2m ?? '-';

    const indicators = [
        { label: 'GDP (USD B)', a: a.gdp, b: bData.gdp, fmt: v => v?.toLocaleString() ?? '-', better: 'high' },
        { label: 'Inflation (%)', a: a.inflation, b: bData.inflation, fmt: v => v != null ? v + '%' : '-', better: 'low' },
        { label: 'Risk Score', a: aRiskScore, b: bRiskScore, fmt: v => (v === '-' || v == null) ? '-' : v + '%', better: 'low' },
        { label: 'Weather (°C)', a: aWeather, b: bWeather, fmt: v => v + '°C', better: 'none' },
        { label: 'Currency', a: a.currency_code, b: bData.currency_code, fmt: v => v ?? '-', better: 'none' },
    ];

    let html = `<div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width:25%">Indicator</th>
                    <th style="width:25%" class="text-center">${a.name}</th>
                    <th style="width:25%" class="text-center">${bData.name}</th>
                    <th style="width:25%" class="text-center">Winner</th>
                </tr>
            </thead>
            <tbody>`;

    indicators.forEach(ind => {
        const va = ind.a ?? 0;
        const vb = ind.b ?? 0;
        let winner = 'Draw';
        if (ind.better === 'high') winner = va > vb ? a.name : vb > va ? bData.name : 'Draw';
        else if (ind.better === 'low') winner = va < vb ? a.name : vb < va ? bData.name : 'Draw';

        const aWin = (ind.better !== 'none') && winner === a.name;
        const bWin = (ind.better !== 'none') && winner === bData.name;

        html += `<tr>
            <td>${ind.label}</td>
            <td class="fw-bold text-center ${aWin ? 'text-success' : ''}">${ind.fmt(ind.a)}</td>
            <td class="fw-bold text-center ${bWin ? 'text-success' : ''}">${ind.fmt(ind.b)}</td>
            <td class="text-center"><span class="badge bg-${winner === 'Draw' ? 'secondary' : 'primary'}">${winner}</span></td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    html += '<div class="mt-2 small text-muted">Risk Score uses Weighted Risk Model: Weather 25% · Inflation 25% · News 25% · FX Rate 25%.</div>';
    html += `<div class="mt-3"><canvas id="dash_compRadar" height="200"></canvas></div>`;
    body.innerHTML = html;

    const maxGdp = Math.max(a.gdp || 0, bData.gdp || 0, 1);
    const radarLabels = ['GDP', 'Inflation', 'Risk', 'Weather'];
    const aNorm = [(a.gdp||0)/maxGdp*100, a.inflation||0, aRiskScore==='-'?0:aRiskScore, (a.weather?.temperature_2m||0)];
    const bNorm = [(bData.gdp||0)/maxGdp*100, bData.inflation||0, bRiskScore==='-'?0:bRiskScore, (bData.weather?.temperature_2m||0)];
    const ctx = document.getElementById('dash_compRadar').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: radarLabels,
            datasets: [
                { label: a.name, data: aNorm, backgroundColor: 'rgba(13,110,253,0.15)', borderColor: '#0d6efd', pointBackgroundColor: '#0d6efd' },
                { label: bData.name, data: bNorm, backgroundColor: 'rgba(220,53,69,0.15)', borderColor: '#dc3545', pointBackgroundColor: '#dc3545' },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function dash_getWatchlistIds() {
    return fetch('/api/watchlist')
        .then(r => r.json())
        .then(items => items.map(c => c.id))
        .catch(() => []);
}

function dash_toggleWatchlist() {
    if (!dash_selectedId) return;
    const token = document.querySelector('meta[name="csrf-token"]').content;

    dash_getWatchlistIds().then(ids => {
        const isIn = ids.includes(dash_selectedId);
        const url = `/api/watchlist/${dash_selectedId}`;
        const method = isIn ? 'DELETE' : 'POST';
        const body = isIn ? null : JSON.stringify({ country_id: dash_selectedId });

        fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body,
        })
            .then(() => {
                dash_updateWatchlistBtn();
                dash_renderWatchlist();
            })
            .catch(() => {});
    });
}

function dash_updateWatchlistBtn() {
    if (!dash_selectedId) return;
    dash_getWatchlistIds().then(ids => {
        const btn = document.getElementById('dash_watchlistBtn');
        if (ids.includes(dash_selectedId)) {
            btn.innerHTML = '<i class="bi bi-star-fill text-warning"></i> Remove from Watchlist';
            btn.className = 'btn btn-sm btn-outline-danger';
        } else {
            btn.innerHTML = '<i class="bi bi-star"></i> Add to Watchlist';
            btn.className = 'btn btn-sm btn-outline-warning';
        }
    });
}

function dash_renderWatchlist() {
    const container = document.getElementById('dash_watchlistContainer');

    fetch('/api/watchlist')
        .then(r => r.json())
        .then(watched => {
            if (!Array.isArray(watched) || watched.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No countries in your watchlist. Add the current country using the button above!</p>';
                return;
            }

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
        })
        .catch(() => {
            container.innerHTML = '<p class="text-danger mb-0">Failed to load watchlist.</p>';
        });
}

function dash_removeWatchlist(id) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`/api/watchlist/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
        .then(() => {
            dash_updateWatchlistBtn();
            dash_renderWatchlist();
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', dash_renderWatchlist);
