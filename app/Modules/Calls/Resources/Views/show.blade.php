{{--
    Call detail. Shows the outcome, the notes and the state of any call-back
    promised — including whether it is still waiting to be made.
--}}
@extends('layouts.master')

@section('title', 'Call')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Call</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('calls.index') }}">Calls</a></li>
                        <li class="breadcrumb-item active">{{ $call->person?->name ?? 'Call' }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>{{ $call->person?->name ?? 'Call' }}</h5>
                        {{-- Wrapped: Cuba styles a direct span child of .card-header
                             as a block-level subtitle (docs/ui-cuba.md). --}}
                        <div class="mt-1">
                            <span class="badge badge-light-{{ $call->statusBadge() }}">{{ $call->statusLabel() }}</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordernone">
                                <tbody>
                                    <tr>
                                        <th class="w-25">Client</th>
                                        <td>
                                            @if($call->person)
                                                @can('persons.view')
                                                    <a href="{{ route('clients.show', $call->person->id) }}">{{ $call->person->name }}</a>
                                                @else
                                                    {{ $call->person->name }}
                                                @endcan
                                                <span class="badge {{ $call->person->typeBadgeClass() }} ms-1">
                                                    {{ $call->person->typeLabel() }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Mobile</th><td>{{ $call->person?->mobile ?? '—' }}</td></tr>
                                    <tr><th>Telecaller</th><td>{{ $call->telecaller?->name ?? '—' }}</td></tr>
                                    <tr><th>Region</th><td>{{ $call->person?->region?->label() ?? '—' }}</td></tr>
                                    <tr>
                                        <th>Called at</th>
                                        <td>{{ $call->called_at?->format('d M Y, H:i') ?? '—' }}
                                            <span class="f-light">(server time)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Call back</th>
                                        <td>
                                            @if($call->follow_up_at === null)
                                                None promised
                                            @elseif($call->follow_up_done_at !== null)
                                                {{ $call->follow_up_at->format('d M Y, H:i') }}
                                                <span class="badge badge-light-success ms-1">
                                                    closed {{ $call->follow_up_done_at->format('d M, H:i') }}
                                                </span>
                                            @elseif($call->isFollowUpDue())
                                                {{ $call->follow_up_at->format('d M Y, H:i') }}
                                                <span class="badge badge-light-warning ms-1">due now</span>
                                            @else
                                                {{ $call->follow_up_at->format('d M Y, H:i') }}
                                                <span class="badge badge-light-secondary ms-1">scheduled</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Notes</th><td>{{ $call->notes ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route('calls.index') }}">Back to Calls</a>
                        @can('calls.delete')
                        <form class="d-inline" method="POST" action="{{ route('calls.destroy', $call->id) }}"
                              onsubmit="return confirm('Delete this call log? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger ms-2" type="submit">Delete call</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
