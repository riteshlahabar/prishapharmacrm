@extends('layouts.master')

@section('title', 'Order Details')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('css/icons/feather-icon/feather.css') }}">
@endsection

@section('content')
<div class="inner-body">
    <div class="inner-body-wrap">
        <div class="page-bar">
            <div class="page-title-bottom">
                <div class="row">
                    <div class="col">
                        <div class="page-title-right">
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
                                    <li class="breadcrumb-item active">{{ $order->order_no }}</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title">Order Details</h5>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary me-1">
                                    <i data-feather="edit-2" class="me-1"></i> Edit
                                </a>
                                <form action="{{ route('orders.destroy', $order) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger me-1">
                                        <i data-feather="trash-2" class="me-1"></i> Delete
                                    </button>
                                </form>
                                <a href="{{ route('orders.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="text-muted" width="35%">Order No</th><td>{{ $order->order_no }}</td></tr>
                                    <tr><th class="text-muted">Customer</th><td>{{ $order->customer->customer_name ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Product</th><td>{{ $order->product->product_name ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Quantity</th><td>{{ $order->quantity }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="text-muted" width="35%">Unit Price</th><td>₹{{ number_format($order->unit_price, 2) }}</td></tr>
                                    <tr><th class="text-muted">Total Price</th><td>₹{{ number_format($order->total_price, 2) }}</td></tr>
                                    <tr><th class="text-muted">Order Date</th><td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}</td></tr>
                                    <tr><th class="text-muted">Status</th><td><span class="badge bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'confirmed' ? 'info' : ($order->status == 'delivered' ? 'success' : 'danger')) }}">{{ ucfirst($order->status) }}</span></td></tr>
                                    <tr><th class="text-muted">Notes</th><td>{{ $order->notes ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Created</th><td>{{ $order->created_at->format('d M Y') }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
