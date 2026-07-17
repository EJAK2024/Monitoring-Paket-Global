const sup_allSuppliers = window.__SUPPLIERS;
let sup_selectedId = null;

let sup_riskTrendChart = null;
let sup_riskPieChart = null;

document.getElementById('supplierSelect').addEventListener('change', function () {
    const id = this.value;
    if (!id) {
        document.getElementById('supplierData').style.display = 'none';
        document.getElementById('noSupplierSelection').style.display = '';
        return;
    }
    sup_selectedId = parseInt(id);
    document.getElementById('noSupplierSelection').style.display = 'none';
    document.getElementById('supplierData').style.display = '';
    sup_loadSupplier(sup_selectedId);
});

function sup_loadSupplier(id) {
    const s = sup_allSuppliers.find(x => x.id === id);
    if (s) sup_renderInfo(s);

    document.getElementById('sup_riskScore').textContent = 'Loading...';
    document.getElementById('sup_riskLabel').textContent = 'Waiting...';
    document.getElementById('sup_riskBar').style.width = '0%';
    document.getElementById('sup_riskLevelBadge').textContent = '-';
    document.getElementById('sup_riskLevelBadge').className = 'badge bg-secondary';
    document.getElementById('sup_rCountry').textContent = '-';
    document.getElementById('sup_rDelivery').textContent = '-';
    document.getElementById('sup_rQuality').textContent = '-';
    document.getElementById('sup_rCompliance').textContent = '-';
    document.getElementById('sup_rFinancial').textContent = '-';

    fetch(`/api/supplier-risk/${id}`)
        .then(r => r.json())
        .then(risk => {
            sup_renderRisk(risk);
            sup_loadHistory(id);
        })
        .catch(() => {
            document.getElementById('sup_riskScore').textContent = 'ERR';
            document.getElementById('sup_riskLabel').textContent = 'Failed to load risk';
        });

    sup_loadAllSuppliers();
}

function sup_renderInfo(s) {
    document.getElementById('sup_category').textContent = s.category ?? '-';
    document.getElementById('sup_country').textContent = s.country?.name ?? '-';
    document.getElementById('sup_cert').textContent = s.certification ?? 'None';
    document.getElementById('sup_status').textContent = (s.status ?? '').toUpperCase();

    const countryName = s.country?.name ?? '';
    const perfHtml = `
        <div class="row g-3 text-center">
            <div class="col-4">
                <h3 class="mb-0">${s.reliability_score ?? '-'}</h3>
                <small class="text-muted">Reliability Score</small>
            </div>
            <div class="col-4">
                <h3 class="mb-0">${s.on_time_delivery_pct ?? '-'}%</h3>
                <small class="text-muted">On-Time Delivery</small>
            </div>
            <div class="col-4">
                <h3 class="mb-0">${s.quality_rating ?? '-'}</h3>
                <small class="text-muted">Quality Rating</small>
            </div>
            <div class="col-6 mt-3">
                <h3 class="mb-0">${s.lead_time_days ?? '-'} days</h3>
                <small class="text-muted">Lead Time</small>
            </div>
            <div class="col-6 mt-3">
                <h3 class="mb-0">${countryName}</h3>
                <small class="text-muted">Country</small>
            </div>
        </div>`;
    document.getElementById('sup_perfMetrics').innerHTML = perfHtml;
}

function sup_renderRisk(risk) {
    const score = risk.total_score ?? 0;
    document.getElementById('sup_riskScore').textContent = score + '%';
    document.getElementById('sup_riskBar').style.width = Math.min(score, 100) + '%';
    const color = score <= 30 ? 'success' : score <= 60 ? 'warning' : 'danger';
    document.getElementById('sup_riskBar').className = 'progress-bar bg-' + color;
    const level = (risk.risk_level || '').toUpperCase();
    document.getElementById('sup_riskLabel').textContent = `${score}% — ${level}`;
    document.getElementById('sup_riskLevelBadge').textContent = level;
    document.getElementById('sup_riskLevelBadge').className = 'badge bg-' + color;

    document.getElementById('sup_rCountry').textContent = Math.round(risk.country_risk_score ?? 0);
    document.getElementById('sup_rDelivery').textContent = Math.round(risk.delivery_risk ?? 0);
    document.getElementById('sup_rQuality').textContent = Math.round(risk.quality_risk ?? 0);
    document.getElementById('sup_rCompliance').textContent = Math.round(risk.compliance_risk ?? 0);
    document.getElementById('sup_rFinancial').textContent = Math.round(risk.financial_risk ?? 0);

    // Pie chart
    const pieData = [
        Math.round(risk.country_risk_score ?? 0),
        Math.round(risk.delivery_risk ?? 0),
        Math.round(risk.quality_risk ?? 0),
        Math.round(risk.compliance_risk ?? 0),
        Math.round(risk.financial_risk ?? 0),
    ];
    if (sup_riskPieChart) sup_riskPieChart.destroy();
    sup_riskPieChart = new Chart(document.getElementById('sup_riskPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Country 40%', 'Delivery 20%', 'Quality 15%', 'Compliance 15%', 'Financial 10%'],
            datasets: [{
                data: pieData,
                backgroundColor: ['#0dcaf0', '#0d6efd', '#ffc107', '#198754', '#6c757d']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function sup_loadHistory(id) {
    fetch(`/api/supplier-risk/${id}/history?days=90`)
        .then(r => r.json())
        .then(data => {
            const series = data.series ?? [];
            if (sup_riskTrendChart) sup_riskTrendChart.destroy();

            if (series.length === 0) return;

            const labels = series.map(s => s.date);
            const totalData = series.map(s => s.total_score ?? 0);

            sup_riskTrendChart = new Chart(document.getElementById('sup_riskTrendChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Total Score',
                        data: totalData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        })
        .catch(() => {});
}

function sup_loadAllSuppliers() {
    fetch('/api/supplier-risk')
        .then(r => r.json())
        .then(risks => {
            const items = Array.isArray(risks) ? risks : [];
            const tbody = document.getElementById('sup_allSuppliersBody');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-muted text-center">No supplier risk data available.</td></tr>';
                return;
            }

            let html = '';
            items.forEach(r => {
                const score = r.total_score ?? 0;
                const color = score <= 30 ? 'success' : score <= 60 ? 'warning' : 'danger';
                const sup = r.supplier ?? {};
                const country = sup.country ?? r.country ?? {};
                html += `<tr>
                    <td><strong>${sup.name ?? '-'}</strong></td>
                    <td>${country.name ?? '-'}</td>
                    <td>${sup.category ?? '-'}</td>
                    <td><span class="badge bg-${color}">${score}</span></td>
                    <td>${(r.risk_level ?? '').toUpperCase()}</td>
                    <td>${Math.round(r.delivery_risk ?? 0)}</td>
                    <td>${Math.round(r.quality_risk ?? 0)}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        })
        .catch(() => {
            document.getElementById('sup_allSuppliersBody').innerHTML =
                '<tr><td colspan="7" class="text-danger text-center">Failed to load supplier data.</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', sup_loadAllSuppliers);
