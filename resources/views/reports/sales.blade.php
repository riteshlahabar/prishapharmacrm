@extends('layouts.master')

@section('title', 'Sales Report')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('css/icons/feather-icon/feather.css') }}">
@endsection

@section('content')
<div class="inner-body">
    <div class="inner-body-wrap">
        <div class="page-bar">
            <div class="page-header">
                <div class="page-title">
                    <h4>Sales Report</h4>
                </div>
                <ul class="breadcrumbs mb-0">
                    <li class="breadcrumb-home"><a href="{{ route('dashboard') }}"><i class="feather-home"></i></a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-link"><a href="{{ route('dashboard') }}">Reports</a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-active">Sales Report</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end mb-3">
                            <div class="col-auto">
                                <label class="form-label">From</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $start_date }}">
                            </div>
                            <div class="col-auto">
                                <label class="form-label">To</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $end_date }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2">Total Orders</h6>
                        <h3 class="mb-0">{{ $totalOrders }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2">Total Revenue</h6>
                        <h3 class="mb-0">₹{{ number_format($totalRevenue, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Order No</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($orders->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center">No orders found</td>
                                    </tr>
                                    @else
                                    @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $order->order_no }}</td>
                                        <td>{{ $order->customer->customer_name ?? '-' }}</td>
                                        <td>{{ $order->product->product_name ?? '-' }}</td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>₹{{ number_format($order->total_price, 2) }}</td>
                                        <td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'confirmed' ? 'info' : ($order->status == 'delivered' ? 'success' : 'danger')) }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
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
