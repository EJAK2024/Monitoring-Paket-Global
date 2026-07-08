const cur_countries = window.__COUNTRIES;
let cur_trendChart = null;
let cur_barChart = null;

document.getElementById('countrySelect').addEventListener('change', function () {
    const id = this.value;
    if (!id) {
        document.getElementById('currencyData').style.display = 'none';
        document.getElementById('noSelection').style.display = '';
        return;
    }
    const country = cur_countries.find(c => c.id === parseInt(id));
    document.getElementById('noSelection').style.display = 'none';
    document.getElementById('currencyData').style.display = '';
    document.getElementById('cur_code').textContent = country.currency_code || '-';
    loadCurrencyAnalysis(country);
});

function loadCurrencyAnalysis(country) {
    const currency = country.currency_code;
    if (!currency) {
        document.getElementById('cur_table').innerHTML = '<p class="text-muted mb-0">No currency data for this country.</p>';
        return;
    }

    fetch(`/api/viz/currency?currency=${currency}&days=90`)
        .then(r => r.json())
        .then(data => {
            const series = data.series || [];
            const latest = series.length ? series[series.length - 1].rate : null;
            const change = data.change_pct ?? 0;

            document.getElementById('cur_latest').textContent = latest != null ? latest.toFixed(4) : '-';
            const changeEl = document.getElementById('cur_change');
            changeEl.textContent = (change > 0 ? '+' : '') + change + '%';
            changeEl.className = 'stat-value ' + (change > 0 ? 'text-danger' : change < 0 ? 'text-success' : '');
            document.getElementById('cur_trendLabel').textContent = currency + ' per USD';

            renderTrend(series, currency);
        });

    fetch('/api/currency?base=USD')
        .then(r => r.json())
        .then(rates => {
            const majors = ['EUR', 'GBP', 'JPY', 'CNY', 'IDR', 'AUD', 'SGD', 'MYR'];
            const ccRate = rates[currency] ?? null;
            let html = '<table class="table table-sm mb-0"><tbody>';
            html += `<tr><td>1 USD</td><td class="text-end fw-bold">${ccRate != null ? ccRate.toFixed(4) + ' ' + currency : '-'}</td></tr>`;
            majors.filter(m => m !== currency).forEach(m => {
                html += `<tr><td>1 ${m}</td><td class="text-end fw-bold">${rates[m] != null && ccRate ? (rates[m] / ccRate).toFixed(4) + ' ' + currency : '-'}</td></tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('cur_table').innerHTML = html;

            renderBar(rates, currency);
        });
}

function renderTrend(series, currency) {
    const labels = series.map(p => p.date);
    const values = series.map(p => p.rate);
    if (cur_trendChart) cur_trendChart.destroy();
    cur_trendChart = new Chart(document.getElementById('cur_trendChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: `${currency} per USD`,
                data: values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 0,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: false } },
        },
    });
}

function renderBar(rates, currency) {
    const majors = ['EUR', 'GBP', 'JPY', 'CNY', 'IDR', 'AUD', 'SGD', 'MYR'].filter(m => m !== currency);
    const values = majors.map(m => rates[m] ?? 0);
    if (cur_barChart) cur_barChart.destroy();
    cur_barChart = new Chart(document.getElementById('cur_barChart'), {
        type: 'bar',
        data: {
            labels: majors,
            datasets: [{
                label: `USD to`,
                data: values.map(v => Math.min(v, 1000)),
                backgroundColor: '#198754',
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                tooltip: { callbacks: { label: ctx => `1 USD = ${values[ctx.dataIndex].toFixed(4)} ${majors[ctx.dataIndex]}` } },
            },
        },
    });
}
