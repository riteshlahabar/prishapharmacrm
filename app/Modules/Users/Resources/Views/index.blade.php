{{--
    Shared list screen for both kinds of account. Rendered by
    App\Modules\Users\Http\Controllers\Web\UserCrudController, which supplies
    $routeName and $titles so MR and telecaller reuse this one file.
    Markup follows docs/ui-cuba.md: .page-body > .container-fluid > .page-title.
--}}
@extends('layouts.master')

@section('title', $titles['plural'])

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6">
                    <h3>{{ $titles['plural'] }}</h3>
                </div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Users</li>
                        <li class="breadcrumb-item active">{{ $titles['plural'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('users::partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5>{{ $titles['plural'] }}</h5>
                                <span class="f-light">{{ $users->total() }} account(s) on record</span>
                            </div>
                            @can("{$routeName}.create")
                            <div class="col-auto">
                                <a class="btn btn-primary" href="{{ route("{$routeName}.create") }}">
                                    <i data-feather="plus" class="me-1"></i> Add {{ $titles['singular'] }}
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <form class="row g-2 mb-4" method="GET" action="{{ route("{$routeName}.index") }}">
                            <div class="col-md-5">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Search name, email or mobile">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All statuses</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Disabled only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i data-feather="search" class="me-1"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route("{$routeName}.index") }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>
                                            @can("{$routeName}.view")
                                                <a class="fw-bold" href="{{ route("{$routeName}.show", $row->id) }}">{{ $row->name }}</a>
                                            @else
                                                <span class="fw-bold">{{ $row->name }}</span>
                                            @endcan
                                        </td>
                                        <td>{{ $row->email }}</td>
                                        <td>{{ $row->mobile ?? '—' }}</td>
                                        <td>
                                            <span class="badge {{ $row->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $row->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can("{$routeName}.view")
                                            <a class="btn btn-link btn-sm p-1" title="View"
                                               href="{{ route("{$routeName}.show", $row->id) }}"><i data-feather="eye"></i></a>
                                            @endcan
                                            @can("{$routeName}.update")
                                            <a class="btn btn-link btn-sm p-1" title="Edit"
                                               href="{{ route("{$routeName}.edit", $row->id) }}"><i data-feather="edit-2"></i></a>
                                            <form class="d-inline" method="POST" action="{{ route("{$routeName}.toggle", $row->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-link btn-sm p-1" type="submit"
                                                        title="{{ $row->is_active ? 'Disable' : 'Enable' }}">
                                                    <i data-feather="{{ $row->is_active ? 'user-x' : 'user-check' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can("{$routeName}.delete")
                                            <form class="d-inline" method="POST"
                                                  action="{{ route("{$routeName}.destroy", $row->id) }}"
                                                  onsubmit="return confirm('Delete {{ $row->name }}? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-link btn-sm p-1 txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center py-4" colspan="6">
                                            No {{ strtolower($titles['plural']) }} found.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">{{ $users->links() }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
