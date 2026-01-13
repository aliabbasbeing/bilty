@extends('layouts.app')

@section('title', 'Consignment Details - ' . $consignment->bilty_no)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-file-invoice"></i> Consignment Details - {{ $consignment->bilty_no }}</h2>
            <div>
                <a href="{{ route('consignments.edit', $consignment) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('consignments.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Company Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-building"></i> Company Information
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4 text-muted">Company:</div>
                    <div class="col-8"><strong>{{ $consignment->company->name }}</strong></div>
                </div>
                @if($consignment->company->address)
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Address:</div>
                        <div class="col-8">{{ $consignment->company->address }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Shipment Details -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shipping-fast"></i> Shipment Details
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4 text-muted">Bilty No:</div>
                    <div class="col-8"><strong>{{ $consignment->bilty_no }}</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Date:</div>
                    <div class="col-8">{{ $consignment->date->format('d M Y') }}</div>
                </div>
                @if($consignment->sender_name)
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Sender:</div>
                        <div class="col-8">{{ $consignment->sender_name }}</div>
                    </div>
                @endif
                <div class="row mb-2">
                    <div class="col-4 text-muted">Route:</div>
                    <div class="col-8">{{ $consignment->from_city }} → {{ $consignment->to_city }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Quantity:</div>
                    <div class="col-8">{{ $consignment->qty }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Vehicle & Driver Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-truck"></i> Vehicle & Driver Information
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4 text-muted">Vehicle No:</div>
                    <div class="col-8"><strong>{{ $consignment->vehicle_no }}</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Ownership:</div>
                    <div class="col-8">
                        @if($consignment->vehicle_owner === 'rental')
                            <span class="badge bg-warning text-dark">Rental</span>
                        @else
                            <span class="badge bg-success">Own</span>
                        @endif
                    </div>
                </div>
                @if($consignment->vehicle_type)
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Vehicle Type:</div>
                        <div class="col-8">{{ $consignment->vehicle_type }}</div>
                    </div>
                @endif
                <div class="row mb-2">
                    <div class="col-4 text-muted">Driver Name:</div>
                    <div class="col-8">{{ $consignment->driver_name }}</div>
                </div>
                @if($consignment->driver_number)
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Driver Contact:</div>
                        <div class="col-8">{{ $consignment->driver_number }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Financial Details -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-money-bill-wave"></i> Financial Details
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4 text-muted">Rate Type:</div>
                    <div class="col-8">
                        @if($consignment->rate_type === 'Fixed')
                            <span class="badge bg-info">Fixed Amount</span>
                        @else
                            <span class="badge bg-primary">Per KM</span>
                        @endif
                    </div>
                </div>
                @if($consignment->rate_type === 'PerKM')
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Distance:</div>
                        <div class="col-8">{{ $consignment->km }} km</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 text-muted">Rate:</div>
                        <div class="col-8">Rs. {{ number_format($consignment->rate) }} per km</div>
                    </div>
                @endif
                <div class="row mb-3">
                    <div class="col-4 text-muted">Amount:</div>
                    <div class="col-8"><strong class="text-primary">Rs. {{ number_format($consignment->amount) }}</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Advance:</div>
                    <div class="col-8">Rs. {{ number_format($consignment->advance) }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Balance:</div>
                    <div class="col-8">
                        @if($consignment->balance > 0)
                            <strong class="text-danger">Rs. {{ number_format($consignment->balance) }}</strong>
                        @else
                            <span class="badge bg-success">Fully Paid</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Notes -->
@if($consignment->details)
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-sticky-note"></i> Additional Notes
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $consignment->details }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Payments -->
@if($consignment->payments->count() > 0)
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-credit-card"></i> Payment History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consignment->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td>Rs. {{ number_format($payment->amount) }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($payment->method) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
