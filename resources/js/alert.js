function al_refresh() {
    fetch('/api/alerts')
        .then(r => r.json())
        .then(data => {
            const items = data.data ?? [];
            const container = document.getElementById('al_alertList');

            if (items.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No alerts. Everything looks good.</p>';
                return;
            }

            let html = '';
            items.forEach(a => {
                const sevColor = a.severity === 'critical' ? 'danger' : a.severity === 'high' ? 'warning' : a.severity === 'medium' ? 'info' : 'secondary';
                const time = new Date(a.created_at).toLocaleString();
                const readClass = a.is_read ? 'opacity-50' : '';
                const icon = a.type === 'country_risk_high' ? '🌍' : a.type === 'supplier_risk_high' ? '🏭' : a.type === 'container_delayed' ? '📦' : a.type === 'weather_storm' ? '⛈️' : '🔔';

                html += `<div class="d-flex gap-3 align-items-start p-3 border-bottom ${readClass}">
                    <div class="fs-4">${icon}</div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong>${a.title}</strong>
                            <div class="d-flex gap-2">
                                <span class="badge bg-${sevColor}">${a.severity}</span>
                                ${!a.is_read ? `<button class="btn btn-sm btn-outline-success py-0 px-1 al-mark-read" data-id="${a.id}" title="Mark read">✓</button>` : ''}
                                <button class="btn btn-sm btn-outline-danger py-0 px-1 al-dismiss" data-id="${a.id}" title="Dismiss">×</button>
                            </div>
                        </div>
                        <p class="mb-0 small text-muted">${a.message ?? ''}</p>
                        <small class="text-muted">${time}</small>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        })
        .catch(() => {
            document.getElementById('al_alertList').innerHTML = '<p class="text-danger mb-0">Failed to load alerts.</p>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    al_refresh();

    var markAllBtn = document.getElementById('al_markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            fetch('/api/alerts/read-all', { method: 'POST' })
                .then(function () { al_refresh(); })
                .catch(function () {});
        });
    }

    var refreshBtn = document.getElementById('al_refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', al_refresh);
    }

    var alertList = document.getElementById('al_alertList');
    if (alertList) {
        alertList.addEventListener('click', function (e) {
            var target = e.target.closest('.al-mark-read');
            if (target) {
                var id = target.dataset.id;
                fetch('/api/alerts/' + id + '/read', { method: 'POST' })
                    .then(function () { al_refresh(); })
                    .catch(function () {});
                return;
            }

            var dismissTarget = e.target.closest('.al-dismiss');
            if (dismissTarget) {
                var id = dismissTarget.dataset.id;
                fetch('/api/alerts/' + id, { method: 'DELETE' })
                    .then(function () { al_refresh(); })
                    .catch(function () {});
            }
        });
    }
});
