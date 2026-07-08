const dash_allCountries = window.__COUNTRIES;
let dash_selectedId = null;
let dash_selectedName = '';
let dash_countryData = null;

let dash_weatherMap = null;
let dash_weatherMarkers = [];

let dash_currencyChart = null;
let dash_econRadar = null;
let dash_tradeChart = null;
let dash_riskPie = null;
let dash_dualChart = null;

function dash_initWeatherMap() {
    if (dash_weatherMap) return;
    dash_weatherMap = L.map('dash_weatherMap', {
        zoomControl: true,
        attributionControl: true,
    }).setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(dash_weatherMap);
}

function dash_invalidateMaps() {
    setTimeout(function () {
        if (dash_weatherMap) dash_weatherMap.invalidateSize();
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
    dash_initWeatherMap();
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

function dash_renderStats(c) {
    document.getElementById('dash_gdp').textContent = c.gdp?.toLocaleString() ?? '-';
    document.getElementById('dash_inflation').textContent = c.inflation != null ? c.inflation + '%' : '-';
    document.getElementById('dash_population').textContent = c.population?.toLocaleString() ?? '-';
    document.getElementById('dash_currency').textContent = c.currency_code ?? '-';
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

function dash_fetchWeatherMap(countryName) {
    document.getElementById('dash_weatherMapLabel').textContent = `Loading weather for ${countryName}...`;

    dash_weatherMarkers.forEach(m => dash_weatherMap.removeLayer(m));
    dash_weatherMarkers = [];

    fetch(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(countryName)}&count=5&language=en&format=json`)
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

            results.forEach(({ loc, data }) => {
                const c = data.current || {};
                const wc = c.weather_code ?? 0;
                const emoji = wc <= 2 ? '☀️' : wc <= 5 ? '⛅' : wc <= 50 ? '🌧️' : '⛈️';

                const marker = L.marker([loc.latitude, loc.longitude], {
                    icon: L.divIcon({
                        className: '',
                        html: `<div style="font-size:24px;line-height:1;text-align:center;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3));">${emoji}</div>
                               <div style="font-size:10px;font-weight:700;color:#1e293b;background:#fff;border-radius:8px;padding:0 4px;text-align:center;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.15);">${c.temperature_2m ?? '-'}°</div>`,
                        iconSize: [36, 40],
                        iconAnchor: [18, 40],
                        popupAnchor: [0, -42],
                    })
                }).addTo(dash_weatherMap)
                .bindPopup(`
                    <div style="min-width:180px;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="font-size:20px;">${emoji}</span>
                            <strong>${loc.name}${loc.country ? ', ' + loc.country : ''}</strong>
                        </div>
                        <hr style="margin:4px 0;">
                        <div style="font-size:0.85rem;">
                            <div>🌡️ <strong>${c.temperature_2m ?? '-'}°C</strong> Temperature</div>
                            <div>🌧️ <strong>${c.precipitation ?? 0} mm</strong> Precipitation</div>
                            <div>💨 <strong>${c.wind_speed_10m ?? '-'} km/h</strong> Wind</div>
                            <div>⛈️ <strong>${dash_stormRisk(c.weather_code ?? 0, c.wind_speed_10m ?? 0)}</strong> Storm Risk (0-100)</div>
                        </div>
                    </div>
                `, { maxWidth: 220 });
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
