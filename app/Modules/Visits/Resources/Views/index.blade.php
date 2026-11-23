{{-- Visit report. Filed by the MR app; the panel reads and can delete. --}}
@extends('layouts.master')

@section('title', 'Visits')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Visits</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Field</li>
                        <li class="breadcrumb-item active">Visits</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Visit report</h5>
                        <span class="f-light">
                            {{ $summary['total'] }} visit(s) ·
                            {{ $summary['persons'] }} client(s) ·
                            {{ $summary['mrs'] }} rep(s) ·
                            {{ $summary['geotagged'] }} with a location
                            @unless(auth()->user()->isAdmin()) · your regions only @endunless
                        </span>
                    </div>

                    <div class="card-body">
                        {{-- The split by purpose, one small card each. The accent
                             cycles by the purpose's position in the enum, so a
                             purpose keeps its colour from screen to screen. --}}
                        @php
                            $accents = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
                            $purposeStats = [];

                            foreach (array_values($purposes) as $i => $purpose) {
                                $n = $summary['by_purpose'][$purpose->value] ?? 0;

                                if ($n > 0) {
                                    $purposeStats[] = [
                                        'label' => $purpose->label(),
                                        'value' => $n,
                                        'colour' => $accents[$i % count($accents)],
                                    ];
                                }
                            }
                        @endphp

                        @include('partials.stat-chips', ['stats' => $purposeStats])

                        <form class="filter-bar row g-2" method="GET" action="{{ route('visits.index') }}">
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
                                <label class="form-label f-light" for="purpose">Purpose</label>
                                <select class="form-select" id="purpose" name="purpose">
                                    <option value="">Any purpose</option>
                                    @foreach($purposes as $purpose)
                                        <option value="{{ $purpose->value }}" @selected(($filters['purpose'] ?? '') === $purpose->value)>
                                            {{ $purpose->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-5 mt-3">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Search client, rep or notes">
                            </div>
                            <div class="col-12 mt-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="without_location" type="checkbox"
                                           name="without_location" value="1" @checked($filters['without_location'] ?? false)>
                                    <label class="form-check-label" for="without_location">No location captured</label>
                                </div>
                                <button class="btn btn-outline-primary ms-2" type="submit">
                                    <i data-feather="search"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('visits.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Medical Rep</th>
                                        <th>Purpose</th>
                                        <th>In</th>
                                        <th>Out</th>
                                        <th>Duration</th>
                                        <th>Geo</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visits as $visit)
                                    <tr>
                                        <td>{{ $visit->visit_date?->format('d M Y') }}</td>
                                        <td>
                                            <a class="fw-bold" href="{{ route('visits.show', $visit->id) }}">
                                                {{ $visit->person?->name ?? '—' }}
                                            </a>
                                            @if($visit->person?->region)
                                                <span class="badge badge-light-primary ms-1">{{ $visit->person->region->code }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $visit->mr?->name ?? '—' }}</td>
                                        <td><span class="badge badge-light-secondary">{{ $visit->purposeLabel() }}</span></td>
                                        <td>{{ $visit->inTimeLabel() }}</td>
                                        <td>{{ $visit->outTimeLabel() }}</td>
                                        <td>{{ $visit->durationLabel() }}</td>
                                        <td>
                                            @if($visit->hasLocation())
                                                <a class="map-pin" target="_blank" rel="noopener noreferrer"
                                                   title="{{ $visit->latitude }}, {{ $visit->longitude }}"
                                                   href="https://www.google.com/maps/search/?api=1&query={{ $visit->latitude }},{{ $visit->longitude }}">
                                                    <i data-feather="map-pin"></i>
                                                </a>
                                            @else
                                                <span class="f-light">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-link btn-icon btn-sm" title="View"
                                               href="{{ route('visits.show', $visit->id) }}"><i data-feather="eye"></i></a>
                                            @can('visits.delete')
                                            <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit->id) }}"
                                                  onsubmit="return confirm('Delete this visit? This cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-icon btn-sm txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="9">No visits match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $visits, 'noun' => 'visit'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
