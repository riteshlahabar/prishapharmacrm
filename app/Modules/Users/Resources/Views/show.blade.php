{{-- Read-only account detail, shared by MR and telecaller. --}}
@extends('layouts.master')

@section('title', $user->name)

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6">
                    <h3>{{ $user->name }}</h3>
                </div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route("{$routeName}.index") }}">{{ $titles['plural'] }}</a></li>
                        <li class="breadcrumb-item active">{{ $user->name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="card-toolbar">
                            <div>
                                <h5>{{ $titles['singular'] }} details</h5>
                            </div>
                            @can("{$routeName}.update")
                            <div class="card-toolbar-actions">
                                <a class="btn btn-primary" href="{{ route("{$routeName}.edit", $user->id) }}">
                                    <i data-feather="edit-2"></i> Edit
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordernone">
                                <tbody>
                                    <tr><th class="w-25">Name</th><td>{{ $user->name }}</td></tr>
                                    <tr><th>Email</th><td>{{ $user->email }}</td></tr>
                                    <tr><th>Mobile</th><td>{{ $user->mobile ?? '—' }}</td></tr>
                                    <tr><th>Role</th><td>{{ $user->roleLabel() }}</td></tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge {{ $user->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $user->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ $user->isTelecaller() ? 'Regions covered' : 'Region' }}</th>
                                        <td>
                                            @forelse($regions as $region)
                                                <span class="badge badge-light-primary me-1">{{ $region->label() }}</span>
                                            @empty
                                                Not assigned
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr><th>Active app tokens</th><td>{{ $user->tokens()->count() }}</td></tr>
                                    <tr><th>Created</th><td>{{ $user->created_at?->format('d M Y, g:i A') ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route("{$routeName}.index") }}">Back to {{ $titles['plural'] }}</a>
                        @if($user->isTelecaller())
                            @can('access.manage')
                            <a class="btn btn-outline-primary" href="{{ route('access.permissions.edit', $user->id) }}">
                                <i data-feather="shield"></i> Permissions
                            </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
