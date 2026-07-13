@extends('layouts.app')

@section('title', 'Manage Ports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Manage Ports</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#portModal">New Port</button>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Country</th><th>Type</th><th>Latitude</th><th>Longitude</th><th>Created</th><th>Action</th></tr>
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
                            <td>
                                <form action="{{ route('admin.ports.destroy', $port) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-muted small">Total: {{ $ports->count() }} pelabuhan</div>
    </div>
</div>

<div class="modal fade" id="portModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.ports.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>New Port</h5></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Port Name</label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Country</label>
                    <select name="country" id="portCountry" class="form-select" required>
                        <option value="">-- Select Country --</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->name }}" data-code="{{ $c->iso_code }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Country Code</label>
                    <input name="country_code" id="portCountryCode" class="form-control" maxlength="2" readonly>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Latitude</label>
                        <input name="latitude" type="number" step="any" class="form-control" placeholder="-90 to 90" required>
                    </div>
                    <div class="col">
                        <label class="form-label">Longitude</label>
                        <input name="longitude" type="number" step="any" class="form-control" placeholder="-180 to 180" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Port Type</label>
                    <select name="port_type" class="form-select">
                        <option value="">-- Select Type --</option>
                        <option value="Container">Container</option>
                        <option value="Multi-purpose">Multi-purpose</option>
                        <option value="Energy">Energy</option>
                        <option value="Industrial">Industrial</option>
                        <option value="Fishing">Fishing</option>
                        <option value="Passenger">Passenger</option>
                        <option value="Military">Military</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('portCountry').addEventListener('change', function () {
    const code = this.options[this.selectedIndex]?.dataset?.code || '';
    document.getElementById('portCountryCode').value = code;
});
</script>
@endsection
