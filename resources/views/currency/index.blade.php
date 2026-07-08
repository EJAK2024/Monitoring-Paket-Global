@extends('layouts.app')

@section('title', 'Currency Impact Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">💱 Currency Impact Dashboard</h4>
    <div class="d-flex gap-2 align-items-center">
        <label class="small text-muted mb-0">Country:</label>
        <select id="countrySelect" class="form-select form-select-sm" style="width: auto;">
            <option value="">Select a country...</option>
            @foreach ($countries as $c)
                @if ($c->currency_code)
                    <option value="{{ $c->id }}" data-currency="{{ $c->currency_code }}">{{ $c->name }} ({{ $c->currency_code }})</option>
                @endif
            @endforeach
        </select>
    </div>
</div>

<div id="noSelection" class="text-center py-5">
    <p class="text-muted fs-5">Select a country above to view its currency impact analysis.</p>
</div>

<div id="currencyData" style="display: none;">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="stat-label">Currency</div>
                <div class="stat-value" id="cur_code">-</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="stat-label">Latest Rate (per 1 USD)</div>
                <div class="stat-value" id="cur_latest">-</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="stat-label">90-Day Change</div>
                <div class="stat-value" id="cur_change">-</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Exchange Rate Trend (last 90 days)</span>
                    <small class="text-muted" id="cur_trendLabel">-</small>
                </div>
                <div class="card-body">
                    <canvas id="cur_trendChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">Snapshot vs Major Currencies</div>
                <div class="card-body" id="cur_table">
                    <p class="text-muted mb-0">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Live Cross Rates (base <span id="cur_baseLabel">USD</span>)</div>
        <div class="card-body">
            <canvas id="cur_barChart" height="200"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>window.__COUNTRIES = @json($countries);</script>
@vite('resources/js/currency.js')
@endsection
