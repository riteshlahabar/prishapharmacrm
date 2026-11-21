@extends('layouts.master')

@section('title', 'Add Customer')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/feather-icon.css') }}">
@endsection

@section('content')
<!-- Inner body start -->
<div class="page-body">
    <!-- Inner body wrapper start -->
    <div class="container-fluid">
        <!-- Page bar start -->
        <div class="page-title-wrap">
            <div class="page-title">
                <div class="row">
                    <div class="col">
                        <div class="page-title-right">
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                    <li class="breadcrumb-item active">Add Customer</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page bar end -->

        <!-- Contact add start -->
        <div class="contact-add">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Add Customer</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('customers.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <!-- Name -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                                        @error('customer_name')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" required>
                                        @error('customer_email')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required>
                                        @error('customer_phone')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Website -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Website</label>
                                        <input type="url" name="customer_website" class="form-control" value="{{ old('customer_website') }}" placeholder="https://example.com">
                                        @error('customer_website')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Address -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="customer_address" class="form-control" value="{{ old('customer_address') }}">
                                    </div>

                                    <!-- City -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="customer_city" class="form-control" value="{{ old('customer_city') }}">
                                    </div>

                                    <!-- State -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">State</label>
                                        <input type="text" name="customer_state" class="form-control" value="{{ old('customer_state') }}">
                                    </div>

                                    <!-- ZIP -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">ZIP / Postal Code</label>
                                        <input type="text" name="customer_zip" class="form-control" value="{{ old('customer_zip') }}">
                                    </div>

                                    <!-- Country -->
                                    <div class="col-xl-6 col-xxl-4">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="customer_country" class="form-control" value="{{ old('customer_country') }}">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Add Customer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact add end -->
    </div>
    <!-- Inner body wrapper end -->
</div>
<!-- Inner body end -->
@endsection

@section('scripts')
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
