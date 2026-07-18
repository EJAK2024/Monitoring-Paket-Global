const wl_countries = window.__COUNTRIES;

function loadWatchlist() {
    const container = document.getElementById('watchlistContainer');

    fetch('/api/watchlist')
        .then(r => r.json())
        .then(watched => {
            if (!Array.isArray(watched) || watched.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No countries in your watchlist. Add some above!</p>';
                return;
            }

            let html = '<div class="row g-3">';
            watched.forEach(c => {
                html += `<div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0">${c.name}</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromWatchlist(${c.id})">&times;</button>
                            </div>
                            <small class="text-muted">GDP: ${c.gdp?.toLocaleString() ?? '-'}B | Inflation: ${c.inflation ?? '-'}%</small>
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

function addToWatchlist() {
    const id = parseInt(document.getElementById('addCountry').value);
    if (!id) return;

    const name = document.getElementById('addCountry').options[document.getElementById('addCountry').selectedIndex].text;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch('/api/watchlist', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ country_id: id }),
    })
        .then(r => {
            if (r.status === 401) { window.location.href = '/login'; return; }
            if (!r.ok) throw new Error('Failed');
            return r.json();
        })
        .then(() => {
            if (typeof showToast === 'function') showToast(name + ' added to Watchlist', 'success');
            document.getElementById('addCountry').value = '';
            loadWatchlist();
        })
        .catch(() => {
            if (typeof showToast === 'function') showToast('Failed to add to watchlist', 'danger');
        });
}

function removeFromWatchlist(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/api/watchlist/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
        .then(r => {
            if (r.status === 401) { window.location.href = '/login'; return; }
            if (!r.ok) throw new Error('Failed');
            return r.json();
        })
        .then(() => {
            if (typeof showToast === 'function') showToast('Removed from Watchlist', 'danger');
            loadWatchlist();
        })
        .catch(() => {
            if (typeof showToast === 'function') showToast('Failed to remove from watchlist', 'danger');
        });
}

document.addEventListener('DOMContentLoaded', loadWatchlist);

window.addToWatchlist = addToWatchlist;
window.removeFromWatchlist = removeFromWatchlist;
