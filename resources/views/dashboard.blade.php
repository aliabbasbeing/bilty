@extends('layouts.app')

@section('title', 'Dashboard - Bilty Management System')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="background: var(--gradient-primary); color: white; border-radius: 20px;">
            <div class="card-body p-4">
                <h1 class="mb-2" style="font-weight: 800;">Bilty Management System</h1>
                <p class="mb-0" style="font-size: 1.125rem; opacity: 0.95;">
                    Welcome back! Manage your bilties, track shipments, and generate reports efficiently.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Total Bilties</div>
                    <h3 class="mb-0 fw-bold">{{ number_format($totalConsignments ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Total Revenue</div>
                    <h3 class="mb-0 fw-bold">Rs. {{ number_format($totalRevenue ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Pending Balance</div>
                    <h3 class="mb-0 fw-bold">Rs. {{ number_format($pendingBalance ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">This Month</div>
                    <h3 class="mb-0 fw-bold">{{ number_format($monthlyConsignments ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <a href="{{ route('consignments.create') }}" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-plus"></i> Create New Bilty
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Quickly add a new bilty record with all shipment details.</p>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-3 mb-3">
        <a href="{{ route('consignments.index') }}" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-list"></i> View All Bilties
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Browse and filter through all your bilty records.</p>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-3 mb-3">
        <a href="{{ route('bills.index') }}" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar"></i> Manage Bills
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Generate and manage bills for multiple bilties.</p>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-3 mb-3">
        <a href="{{ route('companies.index') }}" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-building"></i> Manage Companies
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">View and manage your company/client records.</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Quick Search -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-search"></i> Quick Bilty Search
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.search') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <input type="text" name="bilty_no" class="form-control" 
                                   placeholder="Enter bilty number..." 
                                   value="{{ $biltyNo ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
                
                @if(isset($consignment))
                    <div class="mt-4">
                        <h5 class="mb-3">Search Result</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Bilty No</th>
                                        <th>Date</th>
                                        <th>Company</th>
                                        <th>Vehicle</th>
                                        <th>Route</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>{{ $consignment->bilty_no }}</strong></td>
                                        <td>{{ $consignment->date->format('d M Y') }}</td>
                                        <td>{{ $consignment->company->name }}</td>
                                        <td>{{ $consignment->vehicle_no }}</td>
                                        <td>{{ $consignment->from_city }} → {{ $consignment->to_city }}</td>
                                        <td><strong>Rs. {{ number_format($consignment->amount) }}</strong></td>
                                        <td>Rs. {{ number_format($consignment->balance) }}</td>
                                        <td>
                                            <a href="{{ route('consignments.show', $consignment) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif(isset($biltyNo))
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle"></i> No bilty found for number <strong>{{ $biltyNo }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
