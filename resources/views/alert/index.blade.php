@extends('layouts.app')

@section('title', 'Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Alerts & Notifications</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" id="al_markAllReadBtn">Mark All Read</button>
        <button class="btn btn-sm btn-outline-secondary" id="al_refreshBtn">Refresh</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="al_alertList">
            <p class="text-muted mb-0">Loading alerts...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite('resources/js/alert.js')
@endsection
