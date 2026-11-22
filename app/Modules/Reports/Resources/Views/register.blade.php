{{-- The attendance register: a fixed grid, reps down, days across. Its columns
     are the days in the range, so it cannot share the column-driven table. --}}
@extends('layouts.master')

@section('title', $definition['title'])

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>{{ $definition['title'] }}</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Register</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>{{ $definition['title'] }}</h5>
                        <span class="f-light">
                            {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') }}
                            to {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') }}
                            · {{ $data['totals']['reps'] }} rep(s) over {{ $data['totals']['days'] }} day(s)
                            @unless(auth()->user()->isAdmin()) · your regions only @endunless
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <span class="badge badge-light-success me-1">P — worked and checked out</span>
                            <span class="badge badge-light-warning me-1">O — still on duty</span>
                            <span class="badge badge-light-danger me-1">A — absent</span>
                        </div>

                        @include('reports::_filters')

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="min-width:160px">Medical Rep</th>
                                        @foreach($data['days'] as $day)
                                            <th class="text-center p-1" title="{{ $day['date'] }}">
                                                <span class="d-block f-light" style="font-size:10px">{{ $day['dow'] }}</span>
                                                {{ $day['label'] }}
                                            </th>
                                        @endforeach
                                        <th class="text-end">P</th>
                                        <th class="text-end">A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['rows'] as $row)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $row['name'] }}</span>
                                            <span class="d-block f-light" style="font-size:11px">{{ $row['region'] }}</span>
                                        </td>
                                        @foreach($row['cells'] as $cell)
                                            <td class="text-center p-1">
                                                <span class="badge {{ match ($cell) {
                                                    'P' => 'badge-light-success',
                                                    'O' => 'badge-light-warning',
                                                    default => 'badge-light-danger',
                                                } }}">{{ $cell }}</span>
                                            </td>
                                        @endforeach
                                        <td class="text-end fw-bold">{{ $row['present'] }}</td>
                                        <td class="text-end">{{ $row['absent'] }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center py-4" colspan="{{ count($data['days']) + 3 }}">
                                            No medical reps in scope.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <p class="f-light mt-3 mb-0">
                            A day with no check-in reads as absent: the MR app writes a row when
                            someone checks in, so a missing day is genuinely a missing day.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
