{{-- Call report. Logged by the telecaller app; the panel reads and can delete. --}}
@extends('layouts.master')

@section('title', 'Calls')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Calls</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Field</li>
                        <li class="breadcrumb-item active">Calls</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Call report</h5>
                        <span class="f-light">
                            {{ $summary['total'] }} call(s) ·
                            {{ $summary['persons'] }} person(s) ·
                            {{ $summary['converted'] }} converted ·
                            {{ $summary['follow_ups_open'] }} call-back(s) open
                            @unless(auth()->user()->isAdmin()) · your own calls only @endunless
                        </span>
                    </div>

                    <div class="card-body">
                        @if($summary['by_status'] !== [])
                        <div class="mb-4">
                            @foreach($statuses as $status)
                                @php $n = $summary['by_status'][$status->value] ?? 0; @endphp
                                @if($n > 0)
                                    <span class="badge badge-light-{{ $status->badge() }} me-1">
                                        {{ $status->label() }}: {{ $n }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        <form class="row g-2 mb-4" method="GET" action="{{ route('calls.index') }}">
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
                            @if(auth()->user()->isAdmin())
                            <div class="col-md-3">
                                <label class="form-label f-light" for="telecaller_id">Telecaller</label>
                                <select class="form-select" id="telecaller_id" name="telecaller_id">
                                    <option value="">All telecallers</option>
                                    @foreach($telecallerOptions as $telecaller)
                                        <option value="{{ $telecaller->id }}"
                                            @selected((string) ($filters['telecaller_id'] ?? '') === (string) $telecaller->id)>
                                            {{ $telecaller->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-2">
                                <label class="form-label f-light" for="status">Outcome</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Any outcome</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                                            {{ $status->label() }}
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
                                       placeholder="Search person, telecaller or notes">
                            </div>
                            <div class="col-12 mt-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="follow_up" type="checkbox"
                                           name="follow_up" value="1" @checked($filters['follow_up'] ?? false)>
                                    <label class="form-check-label" for="follow_up">Open call-backs</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="follow_up_due" type="checkbox"
                                           name="follow_up_due" value="1" @checked($filters['follow_up_due'] ?? false)>
                                    <label class="form-check-label" for="follow_up_due">Due now</label>
                                </div>
                                <button class="btn btn-outline-primary ms-2" type="submit">
                                    <i data-feather="search" class="me-1"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('calls.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Called</th>
                                        <th>Person</th>
                                        <th>Telecaller</th>
                                        <th>Outcome</th>
                                        <th>Call back</th>
                                        <th>Notes</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($calls as $call)
                                    <tr>
                                        <td>{{ $call->called_at?->format('d M Y, H:i') }}</td>
                                        <td>
                                            <a class="fw-bold" href="{{ route('calls.show', $call->id) }}">
                                                {{ $call->person?->name ?? '—' }}
                                            </a>
                                            @if($call->person?->region)
                                                <span class="badge badge-light-primary ms-1">{{ $call->person->region->code }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $call->telecaller?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $call->statusBadge() }}">{{ $call->statusLabel() }}</span>
                                        </td>
                                        <td>
                                            @if($call->isFollowUpDue())
                                                <span class="badge badge-light-warning">Due {{ $call->follow_up_at->format('d M, H:i') }}</span>
                                            @else
                                                <span class="f-light">{{ $call->followUpLabel() }}</span>
                                            @endif
                                        </td>
                                        <td class="f-light">{{ Str::limit($call->notes, 40) ?: '—' }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-link btn-sm p-1" title="View"
                                               href="{{ route('calls.show', $call->id) }}"><i data-feather="eye"></i></a>
                                            @can('calls.delete')
                                            <form class="d-inline" method="POST" action="{{ route('calls.destroy', $call->id) }}"
                                                  onsubmit="return confirm('Delete this call log? This cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm p-1 txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="7">No calls match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">{{ $calls->links() }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
