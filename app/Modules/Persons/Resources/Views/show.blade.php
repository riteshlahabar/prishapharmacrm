{{-- Person detail. --}}
@extends('layouts.master')

@section('title', $person->name)

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $person->name }}</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('persons.index') }}">Persons</a></li>
                        <li class="breadcrumb-item active">{{ $person->name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5>{{ $person->name }}</h5>
                                <span class="badge {{ $person->type?->badgeClass() }}">{{ $person->typeLabel() }}</span>
                                <span class="badge {{ $person->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                    {{ $person->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            @can('persons.update')
                            <div class="col-auto">
                                <a class="btn btn-primary" href="{{ route('persons.edit', $person->id) }}">
                                    <i data-feather="edit-2" class="me-1"></i> Edit
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordernone">
                                <tbody>
                                    <tr><th class="w-25">Mobile</th><td>{{ $person->mobile ?? '—' }}</td></tr>
                                    <tr><th>Email</th><td>{{ $person->email ?? '—' }}</td></tr>
                                    @if($person->type?->hasSpecialization())
                                    <tr><th>Specialization</th><td>{{ $person->specialization ?? '—' }}</td></tr>
                                    @endif
                                    <tr><th>City</th><td>{{ $person->city ?? '—' }}</td></tr>
                                    <tr><th>Address</th><td>{{ $person->address ?? '—' }}</td></tr>
                                    <tr>
                                        <th>Region</th>
                                        <td>
                                            @if($person->region)
                                                <span class="badge badge-light-primary">{{ $person->region->label() }}</span>
                                            @else
                                                Not assigned
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Medical rep</th><td>{{ $person->assignedMr?->name ?? 'Unassigned' }}</td></tr>
                                    <tr><th>Telecaller</th><td>{{ $person->assignedTelecaller?->name ?? 'Unassigned' }}</td></tr>
                                    <tr><th>Added</th><td>{{ $person->created_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-light-primary mb-0" role="alert">
                            Visits (Phase 3) and call logs (Phase 4) will appear here once those modules land.
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route('persons.index') }}">Back to Persons</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
