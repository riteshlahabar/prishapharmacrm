@extends('layouts.master')

@section('title', 'Orders')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('css/icons/feather-icon/feather.css') }}">
@endsection

@section('content')
<div class="inner-body">
    <div class="inner-body-wrap">
        <div class="page-bar">
            <div class="page-header">
                <div class="page-title">
                    <h4>Orders</h4>
                </div>
                <ul class="breadcrumbs mb-0">
                    <li class="breadcrumb-home"><a href="{{ route('dashboard') }}"><i class="feather-home"></i></a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-link"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-active">Orders</li>
                </ul>
            </div>
            <div class="page-title-bottom">
                <div class="row">
                    <div class="col">
                        <div class="page-title-right">
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item active">Orders</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('orders.create') }}" class="btn btn-primary">
                            <i data-feather="plus" class="me-1"></i> Add Order
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

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
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $order->order_no }}</td>
                                        <td>{{ $order->customer->customer_name ?? '-' }}</td>
                                        <td>{{ $order->product->product_name ?? '-' }}</td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>₹{{ number_format($order->unit_price, 2) }}</td>
                                        <td>₹{{ number_format($order->total_price, 2) }}</td>
                                        <td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}</td>
                                        <td><span class="badge bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'confirmed' ? 'info' : ($order->status == 'delivered' ? 'success' : 'danger')) }}">{{ ucfirst($order->status) }}</span></td>
                                        <td>
                                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-light" title="View">
                                                <i data-feather="eye"></i>
                                            </a>
                                            <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i data-feather="edit-2"></i>
                                            </a>
                                            <form action="{{ route('orders.destroy', $order) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No orders found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $orders->links() }}
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
