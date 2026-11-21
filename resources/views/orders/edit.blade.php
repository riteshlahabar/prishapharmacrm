@extends('layouts.master')

@section('title', 'Edit Order')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/feather-icon.css') }}">
@endsection

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="page-title-wrap">
            <div class="page-title">
                <div class="row">
                    <div class="col">
                        <div class="page-title-right">
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
                                    <li class="breadcrumb-item active">Edit Order</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title">Edit Order</h5>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('orders.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('orders.update', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Order No <span class="text-danger">*</span></label>
                                        <input type="text" name="order_no" class="form-control @error('order_no') is-invalid @enderror"
                                            value="{{ old('order_no', $order->order_no) }}" required>
                                        @error('order_no')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror" required>
                                            <option value="">-- Select Customer --</option>
                                            @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->customer_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('customer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product <span class="text-danger">*</span></label>
                                        <select name="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id', $order->product_id) == $product->id ? 'selected' : '' }}>
                                                {{ $product->product_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('product_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                            value="{{ old('quantity', $order->quantity) }}" min="1" required>
                                        @error('quantity')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Price (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror"
                                            value="{{ old('unit_price', $order->unit_price) }}" required>
                                        @error('unit_price')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Total Price (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="total_price" class="form-control @error('total_price') is-invalid @enderror"
                                            value="{{ old('total_price', $order->total_price) }}" required>
                                        @error('total_price')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                        <input type="date" name="order_date" class="form-control @error('order_date') is-invalid @enderror"
                                            value="{{ old('order_date', $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : '') }}" required>
                                        @error('order_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="pending" {{ old('status', $order->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ old('status', $order->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                            <option value="delivered" {{ old('status', $order->status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ old('status', $order->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                            rows="3">{{ old('notes', $order->notes) }}</textarea>
                                        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-1">
                                        <i data-feather="check" class="me-1"></i> Update Order
                                    </button>
                                    <a href="{{ route('orders.index') }}" class="btn btn-danger">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
