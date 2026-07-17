@extends('layouts.app')

@section('title', 'Supplier Risk Scoring')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Supplier Risk Scoring</h4>
    <select id="supplierSelect" class="form-select" style="width: auto; max-width: 350px;">
        <option value="">Select a supplier...</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->country->name }})</option>
        @endforeach
    </select>
</div>

<div id="noSupplierSelection" class="text-center py-5">
    <p class="text-muted fs-5">Select a supplier to view risk intelligence.</p>
</div>

<div id="supplierData" style="display: none;">
    <hr class="my-0 mb-4">

    <div class="row g-3 mb-4" id="supplierInfoCards">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Category</div>
                <div class="stat-value" id="sup_category">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Country</div>
                <div class="stat-value" id="sup_country">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Certification</div>
                <div class="stat-value" id="sup_cert">-</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-label">Status</div>
                <div class="stat-value" id="sup_status">-</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span>Supplier Risk Score</span>
                    <span class="badge bg-secondary" id="sup_riskLevelBadge">-</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-auto">
                            <h1 class="mb-0 display-4" id="sup_riskScore">-</h1>
                            <small class="text-muted" id="sup_riskLabel">Waiting...</small>
                        </div>
                        <div class="col">
                            <div class="progress" style="height: 14px;">
                                <div class="progress-bar" id="sup_riskBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 text-center" id="sup_riskComponents">
                        <div class="col">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Country Risk <span class="badge bg-info text-dark">40%</span></small>
                                <strong id="sup_rCountry">0</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Delivery <span class="badge bg-primary">20%</span></small>
                                <strong id="sup_rDelivery">0</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Quality <span class="badge bg-warning text-dark">15%</span></small>
                                <strong id="sup_rQuality">0</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Compliance <span class="badge bg-success">15%</span></small>
                                <strong id="sup_rCompliance">0</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded bg-light">
                                <small class="text-muted d-block">Financial <span class="badge bg-secondary">10%</span></small>
                                <strong id="sup_rFinancial">0</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted text-center">
                        Weighted: Country 40% · Delivery 20% · Quality 15% · Compliance 15% · Financial 10%
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Supplier Performance Metrics</div>
                <div class="card-body" id="sup_perfMetrics">
                    <p class="text-muted mb-0">Select a supplier to see metrics.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Risk Trend (90 days)</div>
                <div class="card-body">
                    <canvas id="sup_riskTrendChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Risk Component Breakdown</div>
                <div class="card-body">
                    <canvas id="sup_riskPieChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">All Suppliers Risk Overview</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="sup_allSuppliersTable">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Country</th>
                            <th>Category</th>
                            <th>Total Score</th>
                            <th>Risk Level</th>
                            <th>Delivery</th>
                            <th>Quality</th>
                        </tr>
                    </thead>
                    <tbody id="sup_allSuppliersBody">
                        <tr><td colspan="7" class="text-muted text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>window.__SUPPLIERS = @json($suppliers);</script>
@vite('resources/js/supplier.js')
@endsection
