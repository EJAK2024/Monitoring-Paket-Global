@extends('layouts.app')

@section('title', 'My Watchlist')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Favorite Monitoring List</h4>
    <div>
        <select id="addCountry" class="form-select d-inline-block" style="width: auto;">
            <option value="">Add country...</option>
            @foreach ($countries as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" onclick="addToWatchlist()">Add</button>
    </div>
</div>

<div class="card">
    <div class="card-header">Watched Countries</div>
    <div class="card-body" id="watchlistContainer">
        <p class="text-muted mb-0">Loading watchlist...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
const wl_countries = @json($countries);

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
</script>
@endsection
