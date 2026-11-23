{{--
    Create / edit screen shared by MR and telecaller. $user is null on create.
    On edit the password fields are optional — blank means "keep current".
--}}
@extends('layouts.master')

@php
    $editing = $user !== null;
    $action = $editing ? route("{$routeName}.update", $user->id) : route("{$routeName}.store");
@endphp

@section('title', ($editing ? 'Edit ' : 'Add ').$titles['singular'])

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6">
                    <h3>{{ $editing ? 'Edit' : 'Add' }} {{ $titles['singular'] }}</h3>
                </div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route("{$routeName}.index") }}">{{ $titles['plural'] }}</a></li>
                        <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Add' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>{{ $titles['singular'] }} details</h5>
                        <span class="f-light">
                            {{ $editing
                                ? 'Leave both password fields empty to keep the current password.'
                                : 'The account signs in with this email and password.' }}
                        </span>
                    </div>

                    <form class="theme-form" method="POST" action="{{ $action }}">
                        @csrf
                        @if($editing)
                            @method('PUT')
                        @endif

                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full name <span class="txt-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror" id="name" type="text"
                                       name="name" value="{{ old('name', $user->name ?? '') }}" required>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobile <span class="txt-danger">*</span></label>
                                <input class="form-control @error('mobile') is-invalid @enderror" id="mobile" type="text"
                                       name="mobile" value="{{ old('mobile', $user->mobile ?? '') }}" required>
                                @error('mobile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email <span class="txt-danger">*</span></label>
                                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                                       name="email" value="{{ old('email', $user->email ?? '') }}" required>
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="role">Role</label>
                                <input class="form-control" id="role" type="text" value="{{ $role->label() }}" disabled>
                                <div class="form-text">Set by the screen you are on; it cannot be changed here.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password">
                                    Password @unless($editing)<span class="txt-danger">*</span>@endunless
                                </label>
                                <input class="form-control @error('password') is-invalid @enderror" id="password"
                                       type="password" name="password" autocomplete="new-password"
                                       @unless($editing) required @endunless>
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">
                                    Confirm password @unless($editing)<span class="txt-danger">*</span>@endunless
                                </label>
                                <input class="form-control" id="password_confirmation" type="password"
                                       name="password_confirmation" autocomplete="new-password"
                                       @unless($editing) required @endunless>
                            </div>

                            {{--
                                One territory for an MR, several for a telecaller — the
                                same field name either way, so the controller and the
                                Regions module decide the shape, not this template.
                            --}}
                            <div class="col-12">
                                <label class="form-label" for="region_ids">
                                    {{ $multipleRegions ? 'Regions covered' : 'Region' }}
                                </label>
                                @php $chosen = old('region_ids', $selectedRegions); @endphp
                                <select class="form-select @error('region_ids') is-invalid @enderror"
                                        id="region_ids" name="region_ids[]"
                                        @if($multipleRegions) multiple size="{{ min(max($regionOptions->count(), 3), 6) }}" @endif>
                                    @unless($multipleRegions)
                                        <option value="">— No region assigned —</option>
                                    @endunless
                                    @forelse($regionOptions as $option)
                                        <option value="{{ $option->id }}"
                                            @selected(in_array((int) $option->id, array_map('intval', (array) $chosen), true))>
                                            {{ $option->name }} ({{ $option->code }})
                                        </option>
                                    @empty
                                        <option value="" disabled>No active regions — create one first</option>
                                    @endforelse
                                </select>
                                @error('region_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('region_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    {{ $multipleRegions
                                        ? 'Ctrl/Cmd-click to pick several. A telecaller sees only clients in these regions.'
                                        : 'The territory this medical rep covers.' }}
                                    @if($regionOptions->isEmpty())
                                        <a href="{{ route('regions.create') }}">Add a region</a>.
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" type="checkbox" name="is_active"
                                           value="1" @checked(old('is_active', $user->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">
                                        Active — a disabled account cannot sign in and its app tokens are revoked
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route("{$routeName}.index") }}">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                {{ $editing ? 'Save changes' : 'Create '.$titles['singular'] }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
