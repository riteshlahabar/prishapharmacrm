@extends('layouts.master')

@section('title', 'Inventory Report')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/feather-icon.css') }}">
@endsection

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="page-title-wrap">
            <div class="page-header">
                <div class="page-title">
                    <h4>Inventory Report</h4>
                </div>
                <ul class="breadcrumbs mb-0">
                    <li class="breadcrumb-home"><a href="{{ route('dashboard') }}"><i class="feather-home"></i></a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-link"><a href="{{ route('dashboard') }}">Reports</a></li>
                    <li class="separator"></li>
                    <li class="breadcrumb-active">Inventory Report</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2">Total Products</h6>
                        <h3 class="mb-0">{{ $totalProducts }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2">Total Stock</h6>
                        <h3 class="mb-0">{{ $totalStock }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2">Low Stock Items</h6>
                        <h3 class="mb-0 text-danger">{{ $lowStockProducts->count() }}</h3>
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
                                        <th>Product Name</th>
                                        <th>Generic Name</th>
                                        <th>Category</th>
                                        <th>MRP (₹)</th>
                                        <th>Stock Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                    <tr style="{{ $product->stock_quantity <= 10 ? 'background-color: #fff3cd' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $product->product_name }}
                                            @if($product->stock_quantity <= 10)
                                                <span class="badge bg-danger ms-1">Low Stock</span>
                                            @endif
                                        </td>
                                        <td>{{ $product->generic_name ?? '-' }}</td>
                                        <td>{{ $product->category ?? '-' }}</td>
                                        <td>₹{{ number_format($product->mrp, 2) }}</td>
                                        <td>{{ $product->stock_quantity }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No products found.</td>
                                    </tr>
                                    @endforelse
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
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
