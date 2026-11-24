{{--
    The client book — doctors, hospitals, medical stores and whatever other
    types the master holds. Filters cover type, region, both assignees and
    status. "Client" is the panel's word for what the code calls a person
    (docs/modules/persons.md).
--}}
@extends('layouts.master')

@section('title', 'Clients')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Clients</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Masters</li>
                        <li class="breadcrumb-item active">Clients</li>
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
                                <h5>Clients</h5>
                                <span class="f-light">
                                    {{ $clients->total() }} client(s) on record
                                    @unless(auth()->user()->isAdmin()) in your regions @endunless
                                </span>
                            </div>
                            <div class="card-toolbar-actions">
                                @can('client_types.view')
                                <a class="btn btn-light" href="{{ route('client-types.index') }}">
                                    <i data-feather="tag"></i> Client Types
                                </a>
                                @endcan
                                @can('persons.create')
                                <a class="btn btn-primary" href="{{ route('clients.create') }}">
                                    <i data-feather="plus"></i> Add Client
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar" method="GET" action="{{ route('clients.index') }}">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input class="form-control" type="search" name="search"
                                           value="{{ $filters['search'] ?? '' }}"
                                           placeholder="Search name, mobile, email or city">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="type">
                                        <option value="">All types</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type->slug }}" @selected(($filters['type'] ?? '') === $type->slug)>
                                                {{ $type->label }}
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
                                    <div class="filter-actions">
                                        <div class="form-check me-2">
                                            <input class="form-check-input" id="unassigned" type="checkbox" name="unassigned"
                                                   value="1" @checked($filters['unassigned'] ?? false)>
                                            <label class="form-check-label" for="unassigned">Missing an MR or a telecaller</label>
                                        </div>
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i data-feather="search"></i> Filter
                                        </button>
                                        <a class="btn btn-light" href="{{ route('clients.index') }}">Reset</a>
                                    </div>
                                </div>
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
                                    @forelse($clients as $person)
                                    <tr>
                                        <td>
                                            <a class="fw-bold" href="{{ route('clients.show', $person->id) }}">{{ $person->name }}</a>
                                            @if($person->specialization)
                                                <div class="f-light">{{ $person->specialization }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $person->typeBadgeClass() }}">{{ $person->typeLabel() }}</span></td>
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
                                            <div class="table-actions">
                                                <a class="btn btn-link btn-icon btn-sm" title="View"
                                                   href="{{ route('clients.show', $person->id) }}"><i data-feather="eye"></i></a>
                                                @can('persons.update')
                                                <a class="btn btn-link btn-icon btn-sm" title="Edit"
                                                   href="{{ route('clients.edit', $person->id) }}"><i data-feather="edit-2"></i></a>
                                                <form class="d-inline" method="POST" action="{{ route('clients.toggle', $person->id) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-link btn-icon btn-sm" type="submit"
                                                            title="{{ $person->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i data-feather="{{ $person->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                                @can('persons.delete')
                                                <form class="d-inline" method="POST" action="{{ route('clients.destroy', $person->id) }}"
                                                      onsubmit="return confirm('Delete {{ $person->name }}?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-link btn-icon btn-icon-danger btn-sm" type="submit" title="Delete">
                                                        <i data-feather="trash-2"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="9">No clients match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $clients, 'noun' => 'client'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
