{{-- The client-type master: what the Type field on a client may be set to. --}}
@extends('layouts.master')

@section('title', 'Client Types')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Client Types</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
                        <li class="breadcrumb-item active">Client Types</li>
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
                                <h5>Client Types</h5>
                                <span class="f-light">
                                    {{ $types->total() }} type(s) — a client is a doctor, a hospital,
                                    a medical store or anything else you add here
                                </span>
                            </div>
                            @can('client_types.create')
                            <div class="card-toolbar-actions">
                                <a class="btn btn-primary" href="{{ route('client-types.create') }}">
                                    <i data-feather="plus"></i> Add Client Type
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar" method="GET" action="{{ route('client-types.index') }}">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <input class="form-control" type="search" name="search"
                                           value="{{ $filters['search'] ?? '' }}" placeholder="Search label or code">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">All statuses</option>
                                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="filter-actions">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i data-feather="search"></i> Filter
                                        </button>
                                        <a class="btn btn-light" href="{{ route('client-types.index') }}">Reset</a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Label</th>
                                        <th>Code</th>
                                        <th>Specialization</th>
                                        <th>Clients</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($types as $type)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $type->badgeClass() }}">{{ $type->label }}</span>
                                            @if($type->isBuiltIn())
                                                <span class="f-light f-12 ms-1">built-in</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $type->slug }}</code></td>
                                        <td>{{ $type->has_specialization ? 'Yes' : '—' }}</td>
                                        <td>{{ $usage[$type->slug] ?? 0 }}</td>
                                        <td>{{ $type->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $type->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $type->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('client_types.update')
                                            <a class="btn btn-link btn-icon btn-sm" title="Edit"
                                               href="{{ route('client-types.edit', $type->id) }}"><i data-feather="edit-2"></i></a>
                                            <form class="d-inline" method="POST" action="{{ route('client-types.toggle', $type->id) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-link btn-icon btn-sm" type="submit"
                                                        title="{{ $type->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i data-feather="{{ $type->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('client_types.delete')
                                            @unless($type->isBuiltIn())
                                            <form class="d-inline" method="POST" action="{{ route('client-types.destroy', $type->id) }}"
                                                  onsubmit="return confirm('Delete {{ $type->label }}?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-icon btn-sm txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endunless
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="7">No client types match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $types, 'noun' => 'client type'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
