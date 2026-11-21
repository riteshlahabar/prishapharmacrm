{{-- Telecallers, with how much of the panel each one can currently reach. --}}
@extends('layouts.master')

@section('title', 'Telecaller permissions')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6">
                    <h3>Telecaller permissions</h3>
                </div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Access</li>
                        <li class="breadcrumb-item active">Permissions</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Who can see what</h5>
                        <span class="f-light">
                            Every telecaller starts with the baseline the telecaller role carries.
                            Anything you tick on a person is added on top, for that person only.
                        </span>
                    </div>

                    <div class="card-body">
                        <form class="row g-2 mb-4" method="GET" action="{{ route('access.permissions.index') }}">
                            <div class="col-md-5">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Search name, email or mobile">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i data-feather="search" class="me-1"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('access.permissions.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Telecaller</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Permissions in force</th>
                                        <th>Granted individually</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($telecallers as $telecaller)
                                    <tr>
                                        <td class="fw-bold">{{ $telecaller->name }}</td>
                                        <td>{{ $telecaller->email }}</td>
                                        <td>
                                            <span class="badge {{ $telecaller->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $telecaller->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td>{{ $counts[$telecaller->id]['effective'] ?? 0 }}</td>
                                        <td>{{ $counts[$telecaller->id]['direct'] ?? 0 }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm"
                                               href="{{ route('access.permissions.edit', $telecaller->id) }}">
                                                <i data-feather="shield" class="me-1"></i> Edit permissions
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center py-4" colspan="6">
                                            No telecallers yet.
                                            <a href="{{ route('telecallers.create') }}">Add one</a> first.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">{{ $telecallers->links() }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
