const viz_countries = window.__COUNTRIES;
const charts = {};

document.getElementById('countrySelect').addEventListener('change', loadViz);

function loadViz() {
    const id = document.getElementById('countrySelect').value;
    if (!id) {
        document.getElementById('vizData').style.display = 'none';
        document.getElementById('noSelection').style.display = '';
        return;
    }
    const country = viz_countries.find(c => c.id === parseInt(id));
    document.getElementById('noSelection').style.display = 'none';
    document.getElementById('vizData').style.display = '';

    const currency = country.currency_code || '';

    fetch(`/api/viz/gdp?country_id=${id}&years=10`).then(r => r.json()).then(d => renderLine('viz_gdp', d.series, 'GDP (B USD)', '#0d6efd'));
    fetch(`/api/viz/inflation?country_id=${id}&years=10`).then(r => r.json()).then(d => renderLine('viz_inflation', d.series, 'Inflation %', '#dc3545'));
    if (currency && currency !== 'USD') {
        fetch(`/api/viz/currency?currency=${currency}&days=90`).then(r => r.json()).then(d => renderLine('viz_currency', d.series, `${currency} per USD`, '#198754'));
    } else {
        renderEmpty('viz_currency', 'No currency data');
    }
    fetch(`/api/viz/risk?country_id=${id}&months=12`).then(r => r.json()).then(d => renderRisk('viz_risk', d.series));
}

function renderLine(canvasId, series, label, color) {
    if (charts[canvasId]) charts[canvasId].destroy();
    const labels = (series || []).map(p => p.date);
    const values = (series || []).map(p => p.value ?? p.rate ?? null);
    charts[canvasId] = new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: { labels, datasets: [{ label, data: values, borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.3, pointRadius: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
    });
}

function renderRisk(canvasId, series) {
    if (charts[canvasId]) charts[canvasId].destroy();
    const labels = (series || []).map(p => p.date);
    const values = (series || []).map(p => p.total_score);
    charts[canvasId] = new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Risk Score',
                data: values,
                borderColor: '#6f42c1',
                backgroundColor: 'rgba(111,66,193,0.15)',
                fill: true, tension: 0.3, pointRadius: 2,
            }],
        },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, title: { display: true, text: 'Weighted: Weather 25% · Inflation 25% · News 25% · FX Rate 25%' } },
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
            },
    });
}

function renderEmpty(canvasId, msg) {
    const el = document.getElementById(canvasId);
    const ctx = el.getContext('2d');
    if (charts[canvasId]) charts[canvasId].destroy();
    charts[canvasId] = new Chart(ctx, {
        type: 'line', data: { labels: [], datasets: [] },
        options: { plugins: { title: { display: true, text: msg } } },
    });
}
