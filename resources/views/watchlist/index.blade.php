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
<script>window.__COUNTRIES = @json($countries);</script>
@vite('resources/js/watchlist.js')
@endsection
