@extends('layouts.master')

@section('title', 'Product Details')

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
                                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                    <li class="breadcrumb-item active">{{ $product->product_name }}</li>
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
                                <h5 class="card-title">Product Details</h5>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                                    <i data-feather="edit-2" class="me-1"></i> Edit
                                </a>
                                <a href="{{ route('products.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="text-muted" width="35%">Product Name</th><td>{{ $product->product_name }}</td></tr>
                                    <tr><th class="text-muted">Product Code</th><td>{{ $product->product_code }}</td></tr>
                                    <tr><th class="text-muted">Generic Name</th><td>{{ $product->generic_name ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Category</th><td>{{ $product->category ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Manufacturer</th><td>{{ $product->manufacturer ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="text-muted" width="35%">MRP</th><td>₹{{ number_format($product->mrp, 2) }}</td></tr>
                                    <tr><th class="text-muted">Selling Price</th><td>₹{{ number_format($product->selling_price, 2) }}</td></tr>
                                    <tr><th class="text-muted">Stock Quantity</th><td>{{ $product->stock_quantity }}</td></tr>
                                    <tr><th class="text-muted">Description</th><td>{{ $product->description ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Created</th><td>{{ $product->created_at->format('d M Y') }}</td></tr>
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
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
