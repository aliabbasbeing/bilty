@extends('layouts.app')

@section('title', 'Consignments - Bilty Management System')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-file-invoice"></i> Consignments</h2>
            <a href="{{ route('consignments.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Bilty
            </a>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filter Consignments
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('consignments.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">All Companies</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" 
                                            {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" 
                                   value="{{ request('from_date') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" 
                                   value="{{ request('to_date') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Bilty no, vehicle, driver..." 
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="{{ route('consignments.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Consignments Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Consignments List ({{ $consignments->total() }} total)
            </div>
            <div class="card-body">
                @if($consignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bilty No</th>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Vehicle</th>
                                    <th>Route</th>
                                    <th>Rate Type</th>
                                    <th>Amount</th>
                                    <th>Advance</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consignments as $consignment)
                                    <tr>
                                        <td><strong>{{ $consignment->bilty_no }}</strong></td>
                                        <td>{{ $consignment->date->format('d M Y') }}</td>
                                        <td>{{ $consignment->company->name }}</td>
                                        <td>
                                            {{ $consignment->vehicle_no }}
                                            @if($consignment->vehicle_owner === 'rental')
                                                <span class="badge bg-warning text-dark">Rental</span>
                                            @else
                                                <span class="badge bg-success">Own</span>
                                            @endif
                                        </td>
                                        <td>{{ $consignment->from_city }} → {{ $consignment->to_city }}</td>
                                        <td>
                                            @if($consignment->rate_type === 'Fixed')
                                                <span class="badge bg-info">Fixed</span>
                                            @else
                                                <span class="badge bg-primary">{{ $consignment->km }} km × Rs. {{ $consignment->rate }}</span>
                                            @endif
                                        </td>
                                        <td><strong>Rs. {{ number_format($consignment->amount) }}</strong></td>
                                        <td>Rs. {{ number_format($consignment->advance) }}</td>
                                        <td>
                                            @if($consignment->balance > 0)
                                                <span class="text-danger fw-bold">Rs. {{ number_format($consignment->balance) }}</span>
                                            @else
                                                <span class="text-success">Paid</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('consignments.show', $consignment) }}" 
                                                   class="btn btn-sm btn-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('consignments.edit', $consignment) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $consignments->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No consignments found.</p>
                        <a href="{{ route('consignments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Bilty
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
