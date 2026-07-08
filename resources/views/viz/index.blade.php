@extends('layouts.app')

@section('title', 'Data Visualization Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">📊 Data Visualization Dashboard</h4>
    <div class="d-flex gap-2 align-items-center">
        <label class="small text-muted mb-0">Country:</label>
        <select id="countrySelect" class="form-select form-select-sm" style="width: auto;">
            <option value="">Select a country...</option>
            @foreach ($countries as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>

    </div>
</div>

<div id="noSelection" class="text-center py-5">
    <p class="text-muted fs-5">Select a country to view its economic, currency and risk trends.</p>
</div>

<div id="vizData" style="display: none;">
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">GDP Trend <small class="text-muted">(USD Billions)</small></div>
                <div class="card-body"><canvas id="viz_gdp" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Inflation Trend <small class="text-muted">(% annual)</small></div>
                <div class="card-body"><canvas id="viz_inflation" height="220"></canvas></div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Currency Trend <small class="text-muted">(per USD, 90d)</small></div>
                <div class="card-body"><canvas id="viz_currency" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Risk Trend <small class="text-muted">(composite score, %)</small></div>
                <div class="card-body"><canvas id="viz_risk" height="220"></canvas></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>window.__COUNTRIES = @json($countries);</script>
@vite('resources/js/viz.js')
@endsection
