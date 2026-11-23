{{--
    Create / edit a client type.

    The code is shown but locked on edit: every client stores it in
    `persons.type`, so moving it would orphan them (see the create migration).
--}}
@extends('layouts.master')

@php
    $editing = $type !== null;
    $action = $editing ? route('client-types.update', $type->id) : route('client-types.store');
    $colours = \App\Modules\ClientTypes\Models\ClientType::COLOURS;
@endphp

@section('title', ($editing ? 'Edit ' : 'Add ').'Client Type')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $editing ? 'Edit' : 'Add' }} Client Type</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('client-types.index') }}">Client Types</a></li>
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
                            <h5>Type details</h5>
                            <span class="f-light">These are the choices the Type field on a client offers.</span>
                        </div>

                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="label">Label <span class="txt-danger">*</span></label>
                                <input class="form-control @error('label') is-invalid @enderror" id="label" type="text"
                                       name="label" value="{{ old('label', $type->label ?? '') }}"
                                       placeholder="Chemist" required>
                                @error('label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">What the panel and both apps show, e.g. "Medical Store".</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="slug">Code</label>
                                <input class="form-control @error('slug') is-invalid @enderror" id="slug" type="text"
                                       name="slug" value="{{ old('slug', $type->slug ?? '') }}"
                                       placeholder="chemist" @disabled($editing)>
                                @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    @if($editing)
                                        Fixed: every client already stores this code.
                                    @else
                                        Leave empty to build it from the label. Lower-case letters, digits and hyphens.
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="colour">Badge colour</label>
                                <select class="form-select @error('colour') is-invalid @enderror" id="colour" name="colour">
                                    @foreach($colours as $colour)
                                        <option value="{{ $colour }}" @selected(old('colour', $type->colour ?? 'primary') === $colour)>
                                            {{ ucfirst($colour) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('colour')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">How the type is tinted in every client list.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="sort_order">Sort order</label>
                                <input class="form-control @error('sort_order') is-invalid @enderror" id="sort_order"
                                       type="number" min="0" max="9999" name="sort_order"
                                       value="{{ old('sort_order', $type->sort_order ?? 0) }}">
                                @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">Low numbers come first in every picker.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="has_specialization" type="checkbox"
                                           name="has_specialization" value="1"
                                           @checked(old('has_specialization', $type->has_specialization ?? false))>
                                    <label class="form-check-label" for="has_specialization">
                                        Carries a specialization — the client form shows the Specialization field for
                                        this type, and clears it for the others
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" type="checkbox" name="is_active"
                                           value="1" @checked(old('is_active', $type->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">
                                        Active — an inactive type stays on the clients that already have it but
                                        drops out of the client form
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route('client-types.index') }}">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                {{ $editing ? 'Save changes' : 'Create Client Type' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
