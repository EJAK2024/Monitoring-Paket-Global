@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Admin Dashboard</h4>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card text-center">
            <div class="stat-label">Users</div>
            <div class="stat-value">{{ $userCount }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center">
            <div class="stat-label">Countries</div>
            <div class="stat-value">{{ $countryCount }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center">
            <div class="stat-label">Ports</div>
            <div class="stat-value">{{ $portCount }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center">
            <div class="stat-label">Articles</div>
            <div class="stat-value">{{ $articleCount }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Manage</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.ports') }}" class="btn btn-outline-primary">Manage Ports</a>
                    <a href="{{ route('admin.articles') }}" class="btn btn-outline-primary">Manage Articles</a>
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">Manage Users</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Risk Overview</div>
            <div class="card-body">
                <canvas id="adminRiskChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite('resources/js/admin.js')
@endsection
