@extends('layouts.app')

@section('title', 'Manage Containers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Manage Containers</h4>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Container ID</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Weight (kg)</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($containers as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td><strong>{{ $c->container_id }}</strong></td>
                            <td>{{ $c->size }}</td>
                            <td>{{ $c->type }}</td>
                            <td>
                                <span class="badge bg-{{ $c->status === 'in_transit' ? 'primary' : ($c->status === 'delivered' ? 'success' : ($c->status === 'delayed' ? 'danger' : ($c->status === 'customs' ? 'warning' : 'info'))) }}">
                                    {{ str_replace('_', ' ', $c->status) }}
                                </span>
                            </td>
                            <td class="small">{{ $c->origin ?? '-' }}</td>
                            <td class="small">{{ $c->destination ?? '-' }}</td>
                            <td>{{ $c->weight_kg ? number_format($c->weight_kg) : '-' }}</td>
                            <td>{{ $c->created_at->format('Y-m-d') }}</td>
                            <td>
                                <form action="{{ route('admin.containers.destroy', $c) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-muted small">Total: {{ $containers->total() }} containers</div>
        <div class="mt-2">{{ $containers->links() }}</div>
    </div>
</div>
@endsection
