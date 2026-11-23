{{--
    Visit detail. Shows both clocks on purpose: what the rep stated about the
    meeting, when the device read the GPS, and when the server received it.
--}}
@extends('layouts.master')

@section('title', 'Visit')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Visit</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('visits.index') }}">Visits</a></li>
                        <li class="breadcrumb-item active">{{ $visit->person?->name ?? 'Visit' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>{{ $visit->person?->name ?? 'Visit' }}</h5>
                        {{-- Wrapped: Cuba styles a direct span child of .card-header
                             as a block-level subtitle, which stretches a badge
                             across the card (docs/ui-cuba.md). --}}
                        <div class="mt-1"><span class="badge badge-light-secondary">{{ $visit->purposeLabel() }}</span></div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordernone">
                                <tbody>
                                    <tr>
                                        <th class="w-25">Client</th>
                                        <td>
                                            @if($visit->person)
                                                @can('persons.view')
                                                    <a href="{{ route('clients.show', $visit->person->id) }}">{{ $visit->person->name }}</a>
                                                @else
                                                    {{ $visit->person->name }}
                                                @endcan
                                                <span class="badge {{ $visit->person->typeBadgeClass() }} ms-1">
                                                    {{ $visit->person->typeLabel() }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Medical rep</th><td>{{ $visit->mr?->name ?? '—' }}</td></tr>
                                    <tr><th>Region</th><td>{{ $visit->person?->region?->label() ?? '—' }}</td></tr>
                                    <tr><th>Visit date</th><td>{{ $visit->visit_date?->format('d M Y') }}</td></tr>
                                    <tr>
                                        <th>Stated times</th>
                                        <td>
                                            {{ $visit->inTimeLabel() }} → {{ $visit->outTimeLabel() }}
                                            <span class="f-light">({{ $visit->durationLabel() }})</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td>
                                            @if($visit->hasLocation())
                                                <a target="_blank" rel="noopener noreferrer"
                                                   href="https://www.google.com/maps/search/?api=1&query={{ $visit->latitude }},{{ $visit->longitude }}">
                                                    {{ $visit->latitude }}, {{ $visit->longitude }}
                                                </a>
                                            @else
                                                Not captured
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Location read at</th>
                                        <td>{{ $visit->location_fetched_at?->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Received by server</th>
                                        <td>{{ $visit->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr><th>Notes</th><td>{{ $visit->notes ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route('visits.index') }}">Back to Visits</a>
                        @can('visits.delete')
                        <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit->id) }}"
                              onsubmit="return confirm('Delete this visit? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-primary txt-danger" type="submit">Delete visit</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
