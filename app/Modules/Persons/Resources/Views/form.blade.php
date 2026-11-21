{{--
    Create / edit a person. The specialization field only applies to doctors,
    so it is shown or hidden from the type select; PersonService clears it
    server-side too, so a type change never leaves stale text behind.
--}}
@extends('layouts.master')

@php
    $editing = $person !== null;
    $action = $editing ? route('persons.update', $person->id) : route('persons.store');
    $currentType = old('type', $person->type?->value ?? 'doctor');
@endphp

@section('title', ($editing ? 'Edit ' : 'Add ').'Person')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $editing ? 'Edit' : 'Add' }} Person</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('persons.index') }}">Persons</a></li>
                        <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Add' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-9">
                @include('partials.flash')

                <form class="theme-form" method="POST" action="{{ $action }}">
                    @csrf
                    @if($editing) @method('PUT') @endif

                    <div class="card">
                        <div class="card-header">
                            <h5>Contact details</h5>
                        </div>

                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Name <span class="txt-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror" id="name" type="text"
                                       name="name" value="{{ old('name', $person->name ?? '') }}" required>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="type">Type <span class="txt-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    @foreach($types as $type)
                                        <option value="{{ $type->value }}" @selected($currentType === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobile <span class="txt-danger">*</span></label>
                                <input class="form-control @error('mobile') is-invalid @enderror" id="mobile" type="text"
                                       name="mobile" value="{{ old('mobile', $person->mobile ?? '') }}" required>
                                @error('mobile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                                       name="email" value="{{ old('email', $person->email ?? '') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6" id="specialization-field">
                                <label class="form-label" for="specialization">Specialization</label>
                                <input class="form-control" id="specialization" type="text" name="specialization"
                                       value="{{ old('specialization', $person->specialization ?? '') }}"
                                       placeholder="Cardiologist">
                                <div class="form-text">Doctors only — cleared automatically for other types.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="city">City</label>
                                <input class="form-control" id="city" type="text" name="city"
                                       value="{{ old('city', $person->city ?? '') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $person->address ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Territory and ownership</h5>
                            <span class="f-light">
                                The region decides which telecallers can see this person at all;
                                the assignments decide who is responsible for them.
                            </span>
                        </div>

                        <div class="card-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="region_id">Region</label>
                                <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id">
                                    <option value="">— None —</option>
                                    @foreach($regionOptions as $region)
                                        <option value="{{ $region->id }}"
                                            @selected((string) old('region_id', $person->region_id ?? '') === (string) $region->id)>
                                            {{ $region->name }} ({{ $region->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('region_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="assigned_mr_id">Medical rep</label>
                                <select class="form-select @error('assigned_mr_id') is-invalid @enderror"
                                        id="assigned_mr_id" name="assigned_mr_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach($mrOptions as $mr)
                                        <option value="{{ $mr->id }}"
                                            @selected((string) old('assigned_mr_id', $person->assigned_mr_id ?? '') === (string) $mr->id)>
                                            {{ $mr->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_mr_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="assigned_telecaller_id">Telecaller</label>
                                <select class="form-select @error('assigned_telecaller_id') is-invalid @enderror"
                                        id="assigned_telecaller_id" name="assigned_telecaller_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach($telecallerOptions as $tc)
                                        <option value="{{ $tc->id }}"
                                            @selected((string) old('assigned_telecaller_id', $person->assigned_telecaller_id ?? '') === (string) $tc->id)>
                                            {{ $tc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_telecaller_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" type="checkbox" name="is_active"
                                           value="1" @checked(old('is_active', $person->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">
                                        Active — inactive persons stay on past visits and calls but drop out of working lists
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route('persons.index') }}">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                {{ $editing ? 'Save changes' : 'Create Person' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    // Specialization only means something for a doctor.
    (function () {
        var type = document.getElementById('type');
        var field = document.getElementById('specialization-field');
        function sync() { field.style.display = type.value === 'doctor' ? '' : 'none'; }
        type.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
