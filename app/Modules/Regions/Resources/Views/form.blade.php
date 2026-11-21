{{-- Create / edit a region. $region is null on create. --}}
@extends('layouts.master')

@php
    $editing = $region !== null;
    $action = $editing ? route('regions.update', $region->id) : route('regions.store');
@endphp

@section('title', ($editing ? 'Edit ' : 'Add ').'Region')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $editing ? 'Edit' : 'Add' }} Region</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('regions.index') }}">Regions</a></li>
                        <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Add' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Region details</h5>
                        <span class="f-light">
                            The code is the short handle used in lists and reports — keep it stable.
                        </span>
                    </div>

                    <form class="theme-form" method="POST" action="{{ $action }}">
                        @csrf
                        @if($editing) @method('PUT') @endif

                        <div class="card-body row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="name">Name <span class="txt-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror" id="name" type="text"
                                       name="name" value="{{ old('name', $region->name ?? '') }}"
                                       placeholder="North Mumbai" required>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-5">
                                <label class="form-label" for="code">Code <span class="txt-danger">*</span></label>
                                <input class="form-control @error('code') is-invalid @enderror" id="code" type="text"
                                       name="code" value="{{ old('code', $region->code ?? '') }}"
                                       placeholder="MUM-N" required>
                                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">Stored uppercase. Letters, digits, - and _ only.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" type="checkbox" name="is_active"
                                           value="1" @checked(old('is_active', $region->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">
                                        Active — inactive regions stay on existing records but are not offered when assigning
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route('regions.index') }}">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                {{ $editing ? 'Save changes' : 'Create Region' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
