{{-- Region master list. Markup per docs/ui-cuba.md. --}}
@extends('layouts.master')

@section('title', 'Regions')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Regions</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Masters</li>
                        <li class="breadcrumb-item active">Regions</li>
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
                                <h5>Regions</h5>
                                <span class="f-light">{{ $regions->total() }} territory(ies) on record</span>
                            </div>
                            @can('regions.create')
                            <div class="card-toolbar-actions">
                                <a class="btn btn-primary" href="{{ route('regions.create') }}">
                                    <i data-feather="plus"></i> Add Region
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar row g-2" method="GET" action="{{ route('regions.index') }}">
                            <div class="col-md-5">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}" placeholder="Search name or code">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All statuses</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i data-feather="search"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('regions.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Medical Reps</th>
                                        <th>Telecallers</th>
                                        <th>Clients</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($regions as $region)
                                    <tr>
                                        <td>{{ $region->id }}</td>
                                        <td>
                                            <a class="fw-bold" href="{{ route('regions.show', $region->id) }}">{{ $region->name }}</a>
                                        </td>
                                        <td><span class="badge badge-light-primary">{{ $region->code }}</span></td>
                                        <td>{{ $usage[$region->id]['mrs'] ?? 0 }}</td>
                                        <td>{{ $usage[$region->id]['telecallers'] ?? 0 }}</td>
                                        <td>{{ $usage[$region->id]['persons'] ?? 0 }}</td>
                                        <td>
                                            <span class="badge {{ $region->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $region->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-link btn-icon btn-sm" title="View"
                                               href="{{ route('regions.show', $region->id) }}"><i data-feather="eye"></i></a>
                                            @can('regions.update')
                                            <a class="btn btn-link btn-icon btn-sm" title="Edit"
                                               href="{{ route('regions.edit', $region->id) }}"><i data-feather="edit-2"></i></a>
                                            <form class="d-inline" method="POST" action="{{ route('regions.toggle', $region->id) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-link btn-icon btn-sm" type="submit"
                                                        title="{{ $region->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i data-feather="{{ $region->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('regions.delete')
                                            <form class="d-inline" method="POST" action="{{ route('regions.destroy', $region->id) }}"
                                                  onsubmit="return confirm('Delete {{ $region->name }}?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-icon btn-sm txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="8">No regions yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $regions, 'noun' => 'region'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
