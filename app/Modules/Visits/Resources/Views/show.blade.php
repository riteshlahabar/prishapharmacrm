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
                                                   href="{{ $visit->mapsUrl() }}">
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
                                        <th>Photo</th>
                                        <td>
                                            @if($visit->hasPhoto())
                                                <span class="badge badge-light-success">Captured</span>
                                                <span class="f-light ms-1">
                                                    {{ $visit->photo_taken_at?->format('d M Y, H:i') ?? 'time not sent' }}
                                                </span>
                                            @else
                                                Not captured
                                            @endif
                                        </td>
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

                @include('partials.product-lines', ['lines' => $visit->products])
            </div>

            {{-- Photo and map. Both are evidence of the same thing — that the
                 rep stood outside this place — so they sit together, beside
                 the figures rather than below them. --}}
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Shopfront photo</h5>
                        <span class="f-light">
                            @if($visit->hasPhoto())
                                Taken on the visit — click to open full size
                            @else
                                The app did not send one with this visit.
                            @endif
                        </span>
                    </div>

                    @if($visit->hasPhoto())
                    <div class="card-body">
                        {{-- Served by VisitPhotoController, not from /storage:
                             the file is on a private disk and inherits this
                             visit's territory scope. --}}
                        <a href="{{ route('visits.photo', $visit->id) }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ route('visits.photo', $visit->id) }}"
                                 class="img-fluid rounded w-100"
                                 style="max-height: 420px; object-fit: cover;"
                                 alt="Photo taken at {{ $visit->person?->name ?? 'this visit' }}">
                        </a>
                    </div>
                    @endif
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Where it was filed</h5>
                        <span class="f-light">
                            @if($visit->hasLocation())
                                The device's reading when the visit was saved
                            @else
                                No coordinates were captured.
                            @endif
                        </span>
                    </div>

                    @if($visit->hasLocation())
                    <div class="card-body">
                        {{-- OpenStreetMap: it embeds with no API key, so the
                             panel needs no billing account and no key in its
                             HTML (Visit::mapEmbedUrl). It does need the
                             browser to reach openstreetmap.org — on a closed
                             network the frame stays blank and the links
                             below still work. --}}
                        <div class="ratio ratio-4x3 rounded overflow-hidden border">
                            <iframe src="{{ $visit->mapEmbedUrl() }}"
                                    title="Map of where this visit was filed"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    style="border: 0;"></iframe>
                        </div>
                        <div class="mt-2 text-center">
                            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                               href="{{ $visit->mapsUrl() }}">
                                <i data-feather="map-pin"></i> Open in Google Maps
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
