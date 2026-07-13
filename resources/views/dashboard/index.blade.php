@extends('layouts.app')

@section('title', 'Global Country Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">🌐 Global Country Dashboard</h4>
    <select id="countrySelect" class="form-select" style="width: auto; max-width: 320px;">
        <option value="">Select a country...</option>
        @foreach ($countries as $country)
            <option value="{{ $country->id }}">{{ $country->name }}</option>
        @endforeach
    </select>
</div>

<div id="noSelection" class="text-center py-5">
    <p class="text-muted fs-5">Select a country above to view all intelligence data.</p>
</div>

<div id="countryData" style="display: none;">
    <hr class="my-0 mb-4">

    {{-- Section 1: Economic Indicators --}}
    <div class="row g-3 mb-4" id="statCards">
        <div class="col-md-3">
            <div class="card stat-card" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-label">GDP (USD B)</div>
                <div class="stat-value" id="dash_gdp">-</div>
                <div class="stat-loader" id="dash_gdpLoader"><div class="bar-loader"></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-label">Inflation (%)</div>
                <div class="stat-value" id="dash_inflation">-</div>
                <div class="stat-loader" id="dash_inflationLoader"><div class="bar-loader"></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-label">Population</div>
                <div class="stat-value" id="dash_population">-</div>
                <div class="stat-loader" id="dash_populationLoader"><div class="bar-loader"></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-label">Currency</div>
                <div class="stat-value" id="dash_currency">-</div>
                <div class="stat-loader" id="dash_currencyLoader"><div class="bar-loader"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Section 2: Risk Score Engine --}}
        <div class="col-md-6">
            <div class="card h-100" data-aos="fade-right">
                <div class="card-header d-flex align-items-center gap-2">
                    <span>Risk Score Engine</span>
                    <span class="badge bg-secondary" id="dash_riskLevelBadge">-</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-auto">
                            <h1 class="mb-0 display-4" id="dash_riskScore">-</h1>
                            <small class="text-muted" id="dash_riskLabel">Waiting...</small>
                        </div>
                        <div class="col">
                            <div class="progress" style="height: 14px;">
                                <div class="progress-bar" id="dash_riskBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 text-center" id="dash_riskComponents">
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Weather <span class="badge bg-info text-dark" title="Bobot">25%</span></small>
                                <strong id="dash_rWeather">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Inflation <span class="badge bg-warning text-dark" title="Bobot">25%</span></small>
                                <strong id="dash_rInflation">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">News <span class="badge bg-primary" title="Bobot">25%</span></small>
                                <strong id="dash_rNews">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">FX Rate <span class="badge bg-success" title="Bobot">25%</span></small>
                                <strong id="dash_rCurrency">0</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted text-center">
                        Weighted Risk Model: Weather 25% · Inflation 25% · News 25% · FX Rate 25%
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Current Weather --}}
        <div class="col-md-6">
            <div class="card h-100" data-aos="fade-left">
                <div class="card-header">Current Weather</div>
                <div class="card-body" id="dash_weatherData">
                    <p class="text-muted mb-0">Select a country to see weather data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3b: Location & Ports Map --}}
    <div class="card mb-4" data-aos="fade-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-geo-alt me-1"></i> Country & Port Map</span>
            <small class="text-muted" id="dash_mapLabel">Select a country</small>
        </div>
        <div class="card-body p-0 position-relative">
            <div id="dash_mapLoader" class="map-loader-overlay">
                <div class="map-loader"></div>
            </div>
            <div id="dash_countryMap" style="height: 420px;"></div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 4: Currency Impact --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card" data-aos="fade-right">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Currency Impact Dashboard</span>
                    <div>
                        <label class="me-1 small">Base:</label>
                        <select id="dash_baseCurrency" class="form-select form-select-sm d-inline-block" style="width: auto;">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="JPY">JPY</option>
                            <option value="IDR">IDR</option>
                            <option value="CNY">CNY</option>
                            <option value="SGD">SGD</option>
                            <option value="AUD">AUD</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="dash_currencyChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" data-aos="fade-left">
                <div class="card-header">Exchange Rate Table</div>
                <div class="card-body" id="dash_rateTable">
                    <p class="text-muted mb-0">Loading rates...</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 5: News Intelligence --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card" data-aos="fade-up">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>News Intelligence</span>
                    <div class="d-flex gap-2">
                        <input type="text" id="dash_newsKeyword" class="form-control form-control-sm" placeholder="Keyword..." value="logistics trade shipping economy" style="width: 220px;">
                        <button class="btn btn-sm btn-primary" onclick="dash_loadNews()">Search</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1 mb-3" id="dash_newsCats">
                        <button class="btn btn-sm btn-outline-secondary" data-q="economy">Economy</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="logistics">Logistics</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="trade">Trade</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="shipping">Shipping</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="geopolitics">Geopolitics</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="inflation">Inflation</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="export OR import">Export/Import</button>
                        <button class="btn btn-sm btn-outline-secondary" data-q="manufacturing">Manufacturing</button>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Positive</small>
                                <strong class="text-success" id="dash_posCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Neutral</small>
                                <strong class="text-secondary" id="dash_neuCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Negative</small>
                                <strong class="text-danger" id="dash_negCount">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded bg-light text-center">
                                <small class="text-muted d-block">Overall</small>
                                <strong id="dash_sentimentLabel">-</strong>
                            </div>
                        </div>
                    </div>
                    <div id="dash_newsContainer">
                        <p class="text-muted mb-0">Loading news...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <hr class="my-4">

<div id="countryDataExtras" style="display: none;">
    {{-- Section 7: Data Visualization --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card" data-aos="zoom-in" data-aos-delay="0">
                <div class="card-header">Economic Profile (Radar)</div>
                <div class="card-body">
                    <canvas id="dash_econRadar" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" data-aos="zoom-in" data-aos-delay="100">
                <div class="card-header">GDP · Exports · Imports</div>
                <div class="card-body">
                    <canvas id="dash_tradeChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card" data-aos="zoom-in" data-aos-delay="0">
                <div class="card-header">Risk Component Breakdown</div>
                <div class="card-body">
                    <canvas id="dash_riskPie" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" data-aos="zoom-in" data-aos-delay="100">
                <div class="card-header">Inflation vs GDP</div>
                <div class="card-body">
                    <canvas id="dash_dualChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 8: Country Comparison --}}
    <div class="card mb-4" data-aos="fade-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Country Comparison Engine</span>
            <div>
                <label class="me-1 small">Compare with:</label>
                <select id="dash_compareCountry" class="form-select form-select-sm d-inline-block" style="width: auto;">
                    <option value="">Select...</option>
                    @foreach ($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body" id="dash_compareBody">
            <p class="text-muted mb-0">Select a second country above to compare.</p>
        </div>
    </div>

    <hr class="my-4">

    {{-- Section 9: Watchlist --}}
    <div class="card mb-4" data-aos="fade-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Favorite Monitoring List</span>
            <button class="btn btn-sm btn-outline-warning" id="dash_watchlistBtn" onclick="dash_toggleWatchlist()">
                <i class="bi bi-star"></i> Add to Watchlist
            </button>
        </div>
        <div class="card-body" id="dash_watchlistContainer">
            <p class="text-muted mb-0">Loading watchlist...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>window.__COUNTRIES = @json($countries);</script>
@vite('resources/js/dashboard.js')
@endsection
