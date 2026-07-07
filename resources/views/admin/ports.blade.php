@extends('layouts.app')

@section('title', 'Manage Ports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Manage Ports</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Country</th><th>Type</th><th>Latitude</th><th>Longitude</th><th>Created</th></tr>
                </thead>
                <tbody>
                    @foreach ($ports as $port)
                        <tr>
                            <td>{{ $port->id }}</td>
                            <td>{{ $port->name }}</td>
                            <td>{{ $port->country }}</td>
                            <td>{{ $port->port_type ?? '-' }}</td>
                            <td>{{ $port->latitude }}</td>
                            <td>{{ $port->longitude }}</td>
                            <td>{{ $port->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
