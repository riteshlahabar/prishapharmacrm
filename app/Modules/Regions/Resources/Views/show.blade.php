{{-- Region detail with who and what it currently covers. --}}
@extends('layouts.master')

@section('title', $region->name)

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $region->name }}</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('regions.index') }}">Regions</a></li>
                        <li class="breadcrumb-item active">{{ $region->name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <div class="card-toolbar">
                            <div><h5>Region details</h5></div>
                            @can('regions.update')
                            <div class="card-toolbar-actions">
                                <a class="btn btn-primary" href="{{ route('regions.edit', $region->id) }}">
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
                                    <tr><th class="w-50">Name</th><td>{{ $region->name }}</td></tr>
                                    <tr><th>Code</th><td><span class="badge badge-light-primary">{{ $region->code }}</span></td></tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge {{ $region->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $region->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr><th>Medical reps in this territory</th><td>{{ $usage['mrs'] ?? 0 }}</td></tr>
                                    <tr><th>Telecallers covering it</th><td>{{ $usage['telecallers'] ?? 0 }}</td></tr>
                                    <tr><th>Clients</th><td>{{ $usage['persons'] ?? 0 }}</td></tr>
                                    <tr><th>Created</th><td>{{ $region->created_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        @if(array_sum($usage ?: [0]) > 0)
                        <div class="alert alert-light-primary mb-0" role="alert">
                            A region in use cannot be deleted. Deactivate it instead — existing records keep it,
                            but it stops being offered when assigning.
                        </div>
                        @endif
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route('regions.index') }}">Back to Regions</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
