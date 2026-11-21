@extends('layouts.master')

@section('title', 'Add Product')

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
                                    <li class="breadcrumb-item active">Add Product</li>
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
                                <h5 class="card-title">Add New Product</h5>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('products.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('products.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
                                            value="{{ old('product_name') }}" required>
                                        @error('product_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Code <span class="text-danger">*</span></label>
                                        <input type="text" name="product_code" class="form-control @error('product_code') is-invalid @enderror"
                                            value="{{ old('product_code') }}" required>
                                        @error('product_code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Generic Name</label>
                                        <input type="text" name="generic_name" class="form-control @error('generic_name') is-invalid @enderror"
                                            value="{{ old('generic_name') }}">
                                        @error('generic_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
                                            value="{{ old('category') }}">
                                        @error('category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Manufacturer</label>
                                        <input type="text" name="manufacturer" class="form-control @error('manufacturer') is-invalid @enderror"
                                            value="{{ old('manufacturer') }}">
                                        @error('manufacturer')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">MRP (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="mrp" class="form-control @error('mrp') is-invalid @enderror"
                                            value="{{ old('mrp') }}" required>
                                        @error('mrp')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Selling Price (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror"
                                            value="{{ old('selling_price') }}" required>
                                        @error('selling_price')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror"
                                            value="{{ old('stock_quantity', 0) }}" required>
                                        @error('stock_quantity')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                            rows="3">{{ old('description') }}</textarea>
                                        @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-feather="check" class="me-1"></i> Save Product
                                    </button>
                                    <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
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
