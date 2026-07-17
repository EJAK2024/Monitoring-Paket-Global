let ct_currentPage = 1;

function ct_search() {
    const q = document.getElementById('containerSearch').value.trim();
    if (!q || q.length < 2) return;

    fetch(`/api/container/search?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            const items = Array.isArray(data) ? data : [];
            if (items.length === 0) {
                document.getElementById('ct_noSelection').style.display = '';
                document.getElementById('ct_result').style.display = 'none';
                return;
            }

            if (items.length === 1) {
                ct_showDetail(items[0].container_id);
            } else {
                let html = '<div class="list-group">';
                items.forEach(c => {
                    const color = ct_statusColor(c.status);
                    html += `<a href="#" class="list-group-item list-group-item-action" data-id="${c.container_id}">
                        <div class="d-flex justify-content-between">
                            <strong>${c.container_id}</strong>
                            <span class="badge bg-${color}">${(c.status || '').replace('_', ' ').toUpperCase()}</span>
                        </div>
                        <small class="text-muted">${c.origin ?? '-'} → ${c.destination ?? '-'}</small>
                    </a>`;
                });
                html += '</div>';
                document.getElementById('ct_result').style.display = '';
                document.getElementById('ct_noSelection').style.display = 'none';
                document.getElementById('ct_infoBody').innerHTML = '<p class="text-muted mb-0">Select a container from search results above.</p>';
                document.getElementById('ct_timelineBody').innerHTML = html;

                document.querySelectorAll('#ct_timelineBody .list-group-item').forEach(el => {
                    el.addEventListener('click', function (e) {
                        e.preventDefault();
                        ct_showDetail(this.dataset.id);
                    });
                });
            }
        })
        .catch(() => {
            document.getElementById('ct_noSelection').style.display = '';
            document.getElementById('ct_result').style.display = 'none';
        });
}



function ct_showDetail(id) {
    document.getElementById('ct_noSelection').style.display = 'none';
    document.getElementById('ct_result').style.display = '';

    document.getElementById('ct_infoBody').innerHTML = '<p class="text-muted mb-0">Loading...</p>';
    document.getElementById('ct_timelineBody').innerHTML = '<p class="text-muted mb-0">Loading timeline...</p>';

    fetch(`/api/container/${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(c => {
            const color = ct_statusColor(c.status);
            document.getElementById('ct_statusBadge').textContent = (c.status || '').replace('_', ' ').toUpperCase();
            document.getElementById('ct_statusBadge').className = 'badge bg-' + color;

            const eta = c.estimated_arrival ? new Date(c.estimated_arrival).toLocaleDateString() : '-';
            const vessel = c.vessel?.name ?? '-';

            let lastScannedText = '-';
            if (c.last_scanned_at) {
                const time = new Date(c.last_scanned_at).toLocaleString();
                const loc = c.current_location ?? '';
                lastScannedText = loc ? `${time} — ${loc}` : time;
            }

            document.getElementById('ct_infoBody').innerHTML = `
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Container ID</td><td class="fw-bold">${c.container_id}</td></tr>
                    <tr><td class="text-muted">Size / Type</td><td>${c.size ?? '-'} / ${c.type ?? '-'}</td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="badge bg-${color}">${(c.status || '').replace('_', ' ').toUpperCase()}</span></td></tr>
                    <tr><td class="text-muted">Origin</td><td>${c.origin ?? '-'}</td></tr>
                    <tr><td class="text-muted">Destination</td><td>${c.destination ?? '-'}</td></tr>
                    <tr><td class="text-muted">Vessel</td><td>${vessel}</td></tr>
                    <tr><td class="text-muted">Weight</td><td>${c.weight_kg ? c.weight_kg.toLocaleString() + ' kg' : '-'}</td></tr>
                    <tr><td class="text-muted">Shipper</td><td>${c.shipper ?? '-'}</td></tr>
                    <tr><td class="text-muted">Consignee</td><td>${c.consignee ?? '-'}</td></tr>
                    <tr><td class="text-muted">Seal Number</td><td>${c.seal_number ?? '-'}</td></tr>
                    <tr><td class="text-muted">Last Scanned</td><td>${lastScannedText}</td></tr>
                    <tr><td class="text-muted">ETA</td><td>${eta}</td></tr>
                </table>`;

            ct_loadTimeline(id);
        })
        .catch(() => {
            document.getElementById('ct_infoBody').innerHTML = '<p class="text-danger mb-0">Container not found.</p>';
        });
}

function ct_loadTimeline(id) {
    fetch(`/api/container/${encodeURIComponent(id)}/timeline`)
        .then(r => r.json())
        .then(events => {
            const items = Array.isArray(events) ? events : [];
            if (items.length === 0) {
                document.getElementById('ct_timelineBody').innerHTML = '<p class="text-muted mb-0">No tracking events yet.</p>';
                return;
            }

            let html = '<div class="timeline-vertical">';
            items.forEach((e, i) => {
                const icon = ct_eventIcon(e.event_type);
                const color = ct_eventColor(e.event_type);
                const time = new Date(e.occurred_at).toLocaleString();
                const vessel = e.vessel?.name ? `<br><small class="text-muted">Vessel: ${e.vessel.name}</small>` : '';
                const remarks = e.remarks ? `<br><small class="text-muted">${e.remarks}</small>` : '';
                const isLast = i === items.length - 1;
                html += `<div class="d-flex gap-3 mb-3 ${isLast ? '' : ''}">
                    <div class="text-center" style="width: 40px;">
                        <div class="fs-5">${icon}</div>
                        ${isLast ? '' : '<div style="width:2px;height:100%;background:#dee2e6;margin:0 auto;"></div>'}
                    </div>
                    <div class="flex-grow-1 pb-3">
                        <strong class="text-${color}">${(e.event_type || '').replace(/_/g, ' ').toUpperCase()}</strong>
                        <div class="small text-muted">${time}</div>
                        <div class="small">${e.location ?? ''}${vessel}${remarks}</div>
                    </div>
                </div>`;
            });
            html += '</div>';
            document.getElementById('ct_timelineBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('ct_timelineBody').innerHTML = '<p class="text-danger mb-0">Failed to load timeline.</p>';
        });
}

function ct_loadAll(page) {
    if (page) ct_currentPage = page;
    const status = document.getElementById('ct_statusFilter').value;
    const size = document.getElementById('ct_sizeFilter').value;

    let url = `/api/container?per_page=15&page=${ct_currentPage}`;
    if (status) url += `&status=${status}`;
    if (size) url += `&size=${size}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const items = data.data ?? [];
            const tbody = document.getElementById('ct_allBody');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">No containers found.</td></tr>';
                document.getElementById('ct_paginationInfo').textContent = '';
                document.getElementById('ct_paginationBtns').innerHTML = '';
                return;
            }

            let html = '';
            items.forEach(c => {
                const color = ct_statusColor(c.status);
                const eta = c.estimated_arrival ? new Date(c.estimated_arrival).toLocaleDateString() : '-';
                const scanned = c.last_scanned_at ? new Date(c.last_scanned_at).toLocaleDateString() : '-';
                const vessel = c.vessel?.name ?? '-';
                const id = c.container_id;
                html += `<tr data-id="${id}" style="cursor:pointer;">
                    <td><strong>${id}</strong></td>
                    <td>${c.size ?? '-'} / ${c.type ?? '-'}</td>
                    <td><span class="badge bg-${color}">${(c.status || '').replace('_', ' ').toUpperCase()}</span></td>
                    <td>${c.origin ?? '-'}</td>
                    <td>${c.destination ?? '-'}</td>
                    <td class="small">${vessel}</td>
                    <td class="small">${scanned}</td>
                    <td class="small">${eta}</td>
                </tr>`;
            });
            tbody.innerHTML = html;

            tbody.querySelectorAll('tr[data-id]').forEach(el => {
                el.addEventListener('click', function () {
                    ct_showDetail(this.dataset.id);
                });
            });

            const total = data.total ?? 0;
            const from = data.from ?? 0;
            const to = data.to ?? 0;
            document.getElementById('ct_paginationInfo').textContent = `Showing ${from}-${to} of ${total}`;

            let btns = '';
            if (data.prev_page_url) {
                btns += `<button class="btn btn-sm btn-outline-primary" onclick="ct_loadAll(${data.current_page - 1})">Prev</button>`;
            }
            if (data.next_page_url) {
                btns += `<button class="btn btn-sm btn-outline-primary" onclick="ct_loadAll(${data.current_page + 1})">Next</button>`;
            }
            document.getElementById('ct_paginationBtns').innerHTML = btns;
        })
        .catch(() => {
            document.getElementById('ct_allBody').innerHTML = '<tr><td colspan="8" class="text-danger text-center">Failed to load containers.</td></tr>';
        });
}

function ct_loadStats() {
    fetch('/api/container/stats')
        .then(r => r.json())
        .then(stats => {
            const statusLabels = {
                in_transit: { label: 'In Transit', color: 'primary' },
                at_port: { label: 'At Port', color: 'info' },
                customs: { label: 'Customs', color: 'warning' },
                delivered: { label: 'Delivered', color: 'success' },
                empty: { label: 'Empty', color: 'secondary' },
                delayed: { label: 'Delayed', color: 'danger' },
            };

            let html = '';
            for (const [key, val] of Object.entries(statusLabels)) {
                const count = stats[key] ?? 0;
                html += `<div class="col-md-2">
                    <div class="card stat-card text-center">
                        <div class="stat-label">${val.label}</div>
                        <div class="stat-value text-${val.color}">${count}</div>
                    </div>
                </div>`;
            }
            html += `<div class="col-md-2">
                <div class="card stat-card text-center">
                    <div class="stat-label">Total</div>
                    <div class="stat-value">${stats.total ?? 0}</div>
                </div>
            </div>`;
            document.getElementById('ct_statsRow').innerHTML = html;
        })
        .catch(() => {});
}

function ct_statusColor(status) {
    const map = {
        in_transit: 'primary',
        at_port: 'info',
        customs: 'warning',
        delivered: 'success',
        empty: 'secondary',
        delayed: 'danger',
    };
    return map[status] ?? 'secondary';
}

function ct_eventIcon(type) {
    const map = {
        loaded: '📦',
        departed: '🚢',
        arrived: '📍',
        discharged: '📥',
        customs_cleared: '✅',
        customs_hold: '🔴',
        gate_out: '🚪',
        delivered: '📬',
        returned: '↩️',
        delayed: '⚠️',
    };
    return map[type] ?? '📋';
}

function ct_eventColor(type) {
    const map = {
        loaded: 'primary',
        departed: 'primary',
        arrived: 'info',
        discharged: 'info',
        customs_cleared: 'success',
        customs_hold: 'danger',
        gate_out: 'secondary',
        delivered: 'success',
        returned: 'secondary',
        delayed: 'danger',
    };
    return map[type] ?? 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
    ct_loadStats();
    ct_loadAll(1);

    document.getElementById('containerSearch').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') ct_search();
    });

    document.getElementById('ct_searchBtn').addEventListener('click', ct_search);

    document.getElementById('ct_statusFilter').addEventListener('change', function () {
        ct_currentPage = 1;
        ct_loadAll(1);
    });

    document.getElementById('ct_sizeFilter').addEventListener('change', function () {
        ct_currentPage = 1;
        ct_loadAll(1);
    });
});
