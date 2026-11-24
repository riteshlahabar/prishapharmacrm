{{-- Create / edit a product. Three fields, all required. --}}
@extends('layouts.master')

@php
    $editing = $product !== null;
    $action = $editing ? route('products.update', $product->id) : route('products.store');
@endphp

@section('title', ($editing ? 'Edit ' : 'Add ').'Product')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $editing ? 'Edit' : 'Add' }} Product</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Add' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <form class="theme-form" method="POST" action="{{ $action }}">
                    @csrf
                    @if($editing) @method('PUT') @endif

                    <div class="card">
                        <div class="card-header">
                            <h5>Product details</h5>
                            <span class="f-light">
                                These are the choices both apps offer — on a visit, and after a call.
                            </span>
                        </div>

                        <div class="card-body row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="name">Name <span class="txt-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror" id="name" type="text"
                                       name="name" value="{{ old('name', $product->name ?? '') }}"
                                       placeholder="Paracetamol 500mg" required>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="box_qty">Box qty (stock) <span class="txt-danger">*</span></label>
                                <input class="form-control @error('box_qty') is-invalid @enderror" id="box_qty"
                                       type="number" min="0" step="1" name="box_qty"
                                       value="{{ old('box_qty', $product->box_qty ?? 0) }}" required>
                                @error('box_qty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    Boxes on hand. Yours to keep — recording this product on a visit or a
                                    call does not change it.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="cost">Cost per box <span class="txt-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input class="form-control @error('cost') is-invalid @enderror" id="cost"
                                           type="number" min="0" step="0.01" name="cost"
                                           value="{{ old('cost', $product->cost ?? '0.00') }}" required>
                                </div>
                                @error('cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">Shown beside the name in both apps' dropdowns.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" type="checkbox" name="is_active"
                                           value="1" @checked(old('is_active', $product->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">
                                        Active — an inactive product drops out of both apps, and the visits
                                        and calls that already name it keep it
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route('products.index') }}">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                {{ $editing ? 'Save changes' : 'Create Product' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
