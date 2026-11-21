@extends('layouts.master')

@section('title', 'Customers')

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
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('dashboard') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Customers</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page bar end -->

        <!-- Contact List start -->
        <div class="contact-list">
            <div class="row">
                <div class="col-sm-12">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title">Customers</h5>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('customers.create') }}" class="btn btn-primary">
                                        <i data-feather="plus" class="me-1"></i> Add Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-responsive-md">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>City</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customers as $customer)
                                        <tr>
                                            <td>{{ $customer->id }}</td>
                                            <td>
                                                <a href="{{ route('customers.show', $customer) }}" class="fw-bold">
                                                    {{ $customer->customer_name }}
                                                </a>
                                            </td>
                                            <td>{{ $customer->customer_email }}</td>
                                            <td>{{ $customer->customer_phone }}</td>
                                            <td>{{ $customer->customer_city ?? '-' }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-link btn-sm" type="button" data-bs-toggle="dropdown">
                                                        <svg class="btn-icon"><use href="#icon-more"></use></svg>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end p-0">
                                                        <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}"><i class="me-1" data-feather="eye"></i> View</a></li>
                                                        <li><a class="dropdown-item" href="{{ route('customers.edit', $customer) }}"><i class="me-1" data-feather="edit-2"></i> Edit</a></li>
                                                        <li>
                                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger"><i class="me-1" data-feather="trash-2"></i> Delete</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No customers found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-3">
                                {{ $customers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact List end -->
    </div>
    <!-- Inner body wrapper end -->
</div>
<!-- Inner body end -->
@endsection

@section('scripts')
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
@endsection
