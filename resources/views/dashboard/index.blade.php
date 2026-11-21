{{--
    Placeholder dashboard. Phase 7 replaces this with the real admin and
    telecaller dashboards (visits, attendance, calls, conversion); until then
    it points at the screens that do exist, scoped to what the viewer may see.
--}}
@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Dashboard</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Welcome, {{ auth()->user()->name }}</h5>
                        <p class="card-text f-light">
                            Master data is in place: regions, persons, medical reps and telecallers.
                            Attendance, visits and call logging arrive in the next phases, and the
                            charts for them will land on this screen.
                        </p>

                        <div class="row g-2 mt-2">
                            @can('regions.view')
                            <div class="col-auto">
                                <a class="btn btn-outline-primary" href="{{ route('regions.index') }}">
                                    <i data-feather="map-pin" class="me-1"></i> Regions
                                </a>
                            </div>
                            @endcan
                            @can('persons.view')
                            <div class="col-auto">
                                <a class="btn btn-outline-primary" href="{{ route('persons.index') }}">
                                    <i data-feather="users" class="me-1"></i> Persons
                                </a>
                            </div>
                            @endcan
                            @can('mrs.view')
                            <div class="col-auto">
                                <a class="btn btn-outline-primary" href="{{ route('mrs.index') }}">
                                    <i data-feather="user-check" class="me-1"></i> Medical Reps
                                </a>
                            </div>
                            @endcan
                            @can('telecallers.view')
                            <div class="col-auto">
                                <a class="btn btn-outline-primary" href="{{ route('telecallers.index') }}">
                                    <i data-feather="headphones" class="me-1"></i> Telecallers
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
