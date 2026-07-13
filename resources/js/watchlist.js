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

    fetch('/api/watchlist', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ country_id: id }),
    })
        .then(r => r.json())
        .then(() => {
            document.getElementById('addCountry').value = '';
            loadWatchlist();
        })
        .catch(() => {});
}

function removeFromWatchlist(id) {
    fetch(`/api/watchlist/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
        .then(() => loadWatchlist())
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', loadWatchlist);
