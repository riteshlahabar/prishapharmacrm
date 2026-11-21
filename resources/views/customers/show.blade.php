@extends('layouts.master')

@section('title', 'Customer Details')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('css/icons/feather-icon/feather.css') }}">
@endsection

@section('content')
<!-- Inner body start -->
<div class="inner-body">
    <!-- Inner body wrapper start -->
    <div class="inner-body-wrap">
        <!-- Page bar start -->
        <div class="page-bar">
            <div class="page-title-bottom">
                <div class="row">
                    <div class="col">
                        <div class="page-title-right">
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                    <li class="breadcrumb-item active">{{ $customer->customer_name }}</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page bar end -->

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title">Customer Details</h5>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
                                    <i data-feather="edit-2" class="me-1"></i> Edit
                                </a>
                                <a href="{{ route('customers.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-muted" width="30%">Name</th>
                                        <td>{{ $customer->customer_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Email</th>
                                        <td><a href="mailto:{{ $customer->customer_email }}" class="text-primary">{{ $customer->customer_email }}</a></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Phone</th>
                                        <td><a href="tel:{{ $customer->customer_phone }}" class="text-primary">{{ $customer->customer_phone }}</a></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Website</th>
                                        <td>{{ $customer->customer_website ? '<a href="' . $customer->customer_website . '" target="_blank" class="text-primary">' . $customer->customer_website . '</a>' : '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-muted" width="30%">Address</th>
                                        <td>{{ $customer->customer_address ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">City</th>
                                        <td>{{ $customer->customer_city ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">State</th>
                                        <td>{{ $customer->customer_state ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">ZIP</th>
                                        <td>{{ $customer->customer_zip ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Country</th>
                                        <td>{{ $customer->customer_country ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Inner body wrapper end -->
</div>
<!-- Inner body end -->
@endsection

@section('scripts')
<script src="{{ asset('js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
