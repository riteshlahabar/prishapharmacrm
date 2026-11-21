{{-- Contact book. Filters cover type, region, both assignees and status. --}}
@extends('layouts.master')

@section('title', 'Persons')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Persons</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Masters</li>
                        <li class="breadcrumb-item active">Persons</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5>Persons</h5>
                                <span class="f-light">
                                    {{ $persons->total() }} doctor(s), hospital(s) and medical store(s)
                                    @unless(auth()->user()->isAdmin()) in your regions @endunless
                                </span>
                            </div>
                            @can('persons.create')
                            <div class="col-auto">
                                <a class="btn btn-primary" href="{{ route('persons.create') }}">
                                    <i data-feather="plus" class="me-1"></i> Add Person
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="row g-2 mb-4" method="GET" action="{{ route('persons.index') }}">
                            <div class="col-md-4">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Search name, mobile, email or city">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="type">
                                    <option value="">All types</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="region_id">
                                    <option value="">All regions</option>
                                    @foreach($regionOptions as $region)
                                        <option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>
                                            {{ $region->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="assigned_mr_id">
                                    <option value="">All reps</option>
                                    @foreach($mrOptions as $mr)
                                        <option value="{{ $mr->id }}" @selected((string) ($filters['assigned_mr_id'] ?? '') === (string) $mr->id)>
                                            {{ $mr->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All statuses</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="unassigned" type="checkbox" name="unassigned"
                                           value="1" @checked($filters['unassigned'] ?? false)>
                                    <label class="form-check-label" for="unassigned">Missing an MR or a telecaller</label>
                                </div>
                                <button class="btn btn-outline-primary ms-2" type="submit">
                                    <i data-feather="search" class="me-1"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('persons.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Mobile</th>
                                        <th>City</th>
                                        <th>Region</th>
                                        <th>Medical Rep</th>
                                        <th>Telecaller</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($persons as $person)
                                    <tr>
                                        <td>
                                            <a class="fw-bold" href="{{ route('persons.show', $person->id) }}">{{ $person->name }}</a>
                                            @if($person->specialization)
                                                <div class="f-light">{{ $person->specialization }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $person->type?->badgeClass() }}">{{ $person->typeLabel() }}</span></td>
                                        <td>{{ $person->mobile ?? '—' }}</td>
                                        <td>{{ $person->city ?? '—' }}</td>
                                        <td>
                                            @if($person->region)
                                                <span class="badge badge-light-primary">{{ $person->region->code }}</span>
                                            @else
                                                <span class="f-light">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $person->assignedMr?->name ?? '—' }}</td>
                                        <td>{{ $person->assignedTelecaller?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge {{ $person->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $person->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-link btn-sm p-1" title="View"
                                               href="{{ route('persons.show', $person->id) }}"><i data-feather="eye"></i></a>
                                            @can('persons.update')
                                            <a class="btn btn-link btn-sm p-1" title="Edit"
                                               href="{{ route('persons.edit', $person->id) }}"><i data-feather="edit-2"></i></a>
                                            <form class="d-inline" method="POST" action="{{ route('persons.toggle', $person->id) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-link btn-sm p-1" type="submit"
                                                        title="{{ $person->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i data-feather="{{ $person->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('persons.delete')
                                            <form class="d-inline" method="POST" action="{{ route('persons.destroy', $person->id) }}"
                                                  onsubmit="return confirm('Delete {{ $person->name }}?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm p-1 txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="9">No persons match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">{{ $persons->links() }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
