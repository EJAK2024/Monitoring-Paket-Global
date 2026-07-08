const wl_countries = window.__COUNTRIES;

function loadWatchlist() {
    const ids = JSON.parse(localStorage.getItem('watchlist') || '[]');
    const watched = wl_countries.filter(c => ids.includes(c.id));
    const container = document.getElementById('watchlistContainer');

    if (watched.length === 0) {
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
}

function addToWatchlist() {
    const id = parseInt(document.getElementById('addCountry').value);
    if (!id) return;
    let ids = JSON.parse(localStorage.getItem('watchlist') || '[]');
    if (!ids.includes(id)) {
        ids.push(id);
        localStorage.setItem('watchlist', JSON.stringify(ids));
    }
    document.getElementById('addCountry').value = '';
    loadWatchlist();
}

function removeFromWatchlist(id) {
    let ids = JSON.parse(localStorage.getItem('watchlist') || '[]');
    ids = ids.filter(i => i !== id);
    localStorage.setItem('watchlist', JSON.stringify(ids));
    loadWatchlist();
}

document.addEventListener('DOMContentLoaded', loadWatchlist);
