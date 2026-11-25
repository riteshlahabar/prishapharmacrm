{{-- The product master: what both apps offer on a visit and after a call. --}}
@extends('layouts.master')

@section('title', 'Products')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Products</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Masters</li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="card-toolbar">
                            <div>
                                <h5>Products</h5>
                                <span class="f-light">
                                    {{ $products->total() }} product(s) — the list the MR and telecaller
                                    apps choose from
                                </span>
                            </div>
                            @can('products.create')
                            <div class="card-toolbar-actions">
                                <a class="btn btn-primary" href="{{ route('products.create') }}">
                                    <i data-feather="plus"></i> Add Product
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar row g-2" method="GET" action="{{ route('products.index') }}">
                            <div class="col-md-5">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}" placeholder="Search by name">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All statuses</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="filter-actions">
                                    <div class="form-check">
                                        <input class="form-check-input" id="in_stock" type="checkbox" name="in_stock"
                                               value="1" @checked($filters['in_stock'] ?? false)>
                                        <label class="form-check-label" for="in_stock">In stock only</label>
                                    </div>
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i data-feather="search"></i> Filter
                                    </button>
                                    <a class="btn btn-light" href="{{ route('products.index') }}">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                {{--
                                    Five columns, four of them short: left to itself the name
                                    eats the row and the narrow headings bunch up against each
                                    other on the right. These shares give each its own room.
                                --}}
                                <colgroup>
                                    <col style="width: 38%">
                                    <col style="width: 15%">
                                    <col style="width: 17%">
                                    <col style="width: 14%">
                                    <col style="width: 16%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-end">Box Qty</th>
                                        <th class="text-end">Cost / Box</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                    <tr>
                                        <td class="fw-bold">{{ $product->name }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ $product->isOutOfStock() ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ number_format($product->box_qty) }}
                                            </span>
                                        </td>
                                        <td class="text-end">₹{{ number_format((float) $product->cost, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $product->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('products.update')
                                            <a class="btn btn-link btn-icon btn-sm" title="Edit"
                                               href="{{ route('products.edit', $product->id) }}"><i data-feather="edit-2"></i></a>
                                            <form class="d-inline" method="POST" action="{{ route('products.toggle', $product->id) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-link btn-icon btn-sm" type="submit"
                                                        title="{{ $product->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i data-feather="{{ $product->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('products.delete')
                                            <form class="d-inline" method="POST" action="{{ route('products.destroy', $product->id) }}"
                                                  onsubmit="return confirm('Delete {{ $product->name }}?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-icon btn-sm txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="5">No products match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $products, 'noun' => 'product'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
