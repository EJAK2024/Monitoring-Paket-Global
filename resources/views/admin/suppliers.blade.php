@extends('layouts.app')

@section('title', 'Manage Suppliers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Manage Suppliers</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">New Supplier</button>
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
                        <th>Name</th>
                        <th>Country</th>
                        <th>Category</th>
                        <th>Reliability</th>
                        <th>On-Time Delivery</th>
                        <th>Quality</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->country->name }}</td>
                            <td>{{ $s->category ?? '-' }}</td>
                            <td>{{ $s->reliability_score ?? '-' }}</td>
                            <td>{{ $s->on_time_delivery_pct ?? '-' }}%</td>
                            <td>{{ $s->quality_rating ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $s->status === 'active' ? 'success' : 'danger' }}">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('admin.suppliers.destroy', $s) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-muted small">Total: {{ $suppliers->count() }} suppliers</div>
    </div>
</div>

<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.suppliers.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>New Supplier</h5></div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Supplier Name</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">-- Select --</option>
                            <option>Electronics</option>
                            <option>Textile</option>
                            <option>Automotive</option>
                            <option>Chemicals</option>
                            <option>Commodities</option>
                            <option>Logistics</option>
                            <option>Industrial</option>
                            <option>Pharmaceutical</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Country</label>
                        <select name="country_id" class="form-select" required>
                            <option value="">-- Select Country --</option>
                            @foreach ($countries as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Certification</label>
                        <input name="certification" class="form-control" placeholder="e.g. ISO 9001">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Reliability Score (0-100)</label>
                        <input name="reliability_score" type="number" min="0" max="100" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label">On-Time Delivery (%)</label>
                        <input name="on_time_delivery_pct" type="number" step="0.01" min="0" max="100" class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Quality Rating (0-100)</label>
                        <input name="quality_rating" type="number" min="0" max="100" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label">Lead Time (days)</label>
                        <input name="lead_time_days" type="number" min="0" class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Contact Email</label>
                        <input name="contact_email" type="email" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label">Contact Phone</label>
                        <input name="contact_phone" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
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
