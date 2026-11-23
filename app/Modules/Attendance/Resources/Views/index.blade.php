{{-- Daily presence report. Read-only: rows come from the MR app. --}}
@extends('layouts.master')

@section('title', 'Attendance')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Attendance</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Field</li>
                        <li class="breadcrumb-item active">Attendance</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Daily presence</h5>
                        <span class="f-light">
                            {{ $summary['rows'] }} record(s) ·
                            {{ $summary['on_duty'] }} still on duty ·
                            {{ $summary['completed'] }} completed.
                            Times are set by the server when the rep taps the button.
                        </span>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar row g-2" method="GET" action="{{ route('attendance.index') }}">
                            <div class="col-md-2">
                                <label class="form-label f-light" for="date_from">From</label>
                                <input class="form-control" id="date_from" type="date" name="date_from"
                                       value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label f-light" for="date_to">To</label>
                                <input class="form-control" id="date_to" type="date" name="date_to"
                                       value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label f-light" for="mr_id">Medical rep</label>
                                <select class="form-select" id="mr_id" name="mr_id">
                                    <option value="">All reps</option>
                                    @foreach($mrOptions as $mr)
                                        <option value="{{ $mr->id }}" @selected((string) ($filters['mr_id'] ?? '') === (string) $mr->id)>
                                            {{ $mr->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label f-light" for="region_id">Region</label>
                                <select class="form-select" id="region_id" name="region_id">
                                    <option value="">All regions</option>
                                    @foreach($regionOptions as $region)
                                        <option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>
                                            {{ $region->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label f-light" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Any status</option>
                                    <option value="on_duty" @selected(($filters['status'] ?? '') === 'on_duty')>On duty</option>
                                    <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                                </select>
                            </div>
                            <div class="col-12 mt-3">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i data-feather="search"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('attendance.index') }}">Today</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Medical Rep</th>
                                        <th>Region</th>
                                        <th>Check in</th>
                                        <th>Check out</th>
                                        <th>Worked</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $row)
                                    <tr>
                                        <td>{{ $row->date?->format('d M Y') }}</td>
                                        <td class="fw-bold">{{ $row->mr?->name ?? '—' }}</td>
                                        <td>
                                            @if($row->mr?->region)
                                                <span class="badge badge-light-primary">{{ $row->mr->region->code }}</span>
                                            @else
                                                <span class="f-light">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $row->check_in_at?->format('H:i') ?? '—' }}
                                            @include('attendance::partials.map-link', [
                                                'lat' => $row->check_in_lat, 'lng' => $row->check_in_lng,
                                            ])
                                        </td>
                                        <td>
                                            {{ $row->check_out_at?->format('H:i') ?? '—' }}
                                            @include('attendance::partials.map-link', [
                                                'lat' => $row->check_out_lat, 'lng' => $row->check_out_lng,
                                            ])
                                        </td>
                                        <td>{{ $row->workedLabel() }}</td>
                                        <td>
                                            <span class="badge {{ $row->isCheckedOut() ? 'badge-light-success' : ($row->isCheckedIn() ? 'badge-light-primary' : 'badge-light-danger') }}">
                                                {{ $row->status() }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="7">No attendance for this filter.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $rows, 'noun' => 'record'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
