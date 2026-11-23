{{-- Reports landing page: what each report answers, so nobody opens six to find one. --}}
@extends('layouts.master')

@section('title', 'Reports')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Reports</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            @include('partials.flash')

            @foreach($reports as $slug => $report)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <h5>{{ $report['title'] }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="f-light">{{ $report['blurb'] }}</p>
                        <a class="btn btn-primary" href="{{ route('reports.show', $slug) }}">
                            <i data-feather="bar-chart-2"></i> Open
                        </a>
                        @can('reports.export')
                        <a class="btn btn-light" href="{{ route('reports.export', $slug) }}">
                            <i data-feather="download"></i> CSV
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @unless(auth()->user()->isAdmin())
        <div class="row">
            <div class="col-12">
                <div class="alert alert-light-primary" role="alert">
                    Every figure here is limited to the regions you cover.
                </div>
            </div>
        </div>
        @endunless

    </div>
</div>
@endsection
