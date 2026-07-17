@extends('layouts.app')

@section('title', 'Container Tracking')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Container Tracking</h4>
    <div class="d-flex gap-2">
        <input type="text" id="containerSearch" class="form-control form-control-sm" placeholder="Search container ID, shipper..." style="width: 300px;">
        <button class="btn btn-sm btn-primary" id="ct_searchBtn">Search</button>
    </div>
</div>

<div id="ct_statsRow" class="row g-3 mb-4"></div>

<div id="ct_noSelection" class="text-center py-5">
    <p class="text-muted fs-5">Search for a container by ID, shipper, or destination.</p>
</div>

<div id="ct_result" style="display: none;">
    <hr class="my-0 mb-4">

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span>Container Info</span>
                    <span class="badge bg-secondary" id="ct_statusBadge">-</span>
                </div>
                <div class="card-body" id="ct_infoBody">
                    <p class="text-muted mb-0">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">Tracking Timeline</div>
                <div class="card-body" id="ct_timelineBody">
                    <p class="text-muted mb-0">Loading timeline...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Containers</span>
        <div class="d-flex gap-2">
            <select id="ct_statusFilter" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Status</option>
                <option value="in_transit">In Transit</option>
                <option value="at_port">At Port</option>
                <option value="customs">Customs</option>
                <option value="delivered">Delivered</option>
                <option value="empty">Empty</option>
                <option value="delayed">Delayed</option>
            </select>
            <select id="ct_sizeFilter" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Sizes</option>
                <option value="20ft">20ft</option>
                <option value="40ft">40ft</option>
                <option value="40HC">40HC</option>
                <option value="45ft">45ft</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Container ID</th>
                        <th>Size/Type</th>
                        <th>Status</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Vessel</th>
                        <th>Last Scanned</th>
                        <th>ETA</th>
                    </tr>
                </thead>
                <tbody id="ct_allBody">
                    <tr><td colspan="8" class="text-muted text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="mt-2 d-flex justify-content-between align-items-center">
            <small class="text-muted" id="ct_paginationInfo"></small>
            <div class="d-flex gap-1" id="ct_paginationBtns"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite('resources/js/container.js')
@endsection
