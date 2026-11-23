{{-- Message log. Written in the telecaller app, read in the MR app. --}}
@extends('layouts.master')

@section('title', 'Messages')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Messages</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Field</li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        <h5>Message log</h5>
                        <span class="f-light">
                            {{ $summary['total'] }} message(s) ·
                            {{ $summary['unread'] }} still unread ·
                            {{ $summary['senders'] }} sender(s) ·
                            {{ $summary['recipients'] }} recipient(s)
                            @unless(auth()->user()->isAdmin()) · yours only @endunless
                        </span>
                    </div>

                    <div class="card-body">
                        <form class="filter-bar row g-2" method="GET" action="{{ route('messages.index') }}">
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
                                <label class="form-label f-light" for="from_user_id">Sender</label>
                                <select class="form-select" id="from_user_id" name="from_user_id">
                                    <option value="">All telecallers</option>
                                    @foreach($telecallerOptions as $telecaller)
                                        <option value="{{ $telecaller->id }}"
                                            @selected((string) ($filters['from_user_id'] ?? '') === (string) $telecaller->id)>
                                            {{ $telecaller->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label f-light" for="to_user_id">Recipient</label>
                                <select class="form-select" id="to_user_id" name="to_user_id">
                                    <option value="">All reps</option>
                                    @foreach($mrOptions as $mr)
                                        <option value="{{ $mr->id }}"
                                            @selected((string) ($filters['to_user_id'] ?? '') === (string) $mr->id)>
                                            {{ $mr->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-2">
                                <label class="form-label f-light" for="read">State</label>
                                <select class="form-select" id="read" name="read">
                                    <option value="">Any state</option>
                                    <option value="unread" @selected(($filters['read'] ?? '') === 'unread')>Unread</option>
                                    <option value="read" @selected(($filters['read'] ?? '') === 'read')>Read</option>
                                </select>
                            </div>
                            <div class="col-md-5 mt-3">
                                <input class="form-control" type="search" name="search"
                                       value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Search subject, body, client or people">
                            </div>
                            <div class="col-12 mt-3">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i data-feather="search"></i> Filter
                                </button>
                                <a class="btn btn-light" href="{{ route('messages.index') }}">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Sent</th>
                                        <th>Subject</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>About</th>
                                        <th>State</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($messages as $message)
                                    <tr>
                                        <td>{{ $message->created_at?->format('d M Y, H:i') }}</td>
                                        <td>
                                            <a class="fw-bold" href="{{ route('messages.show', $message->id) }}">
                                                {{ Str::limit($message->subject, 40) }}
                                            </a>
                                        </td>
                                        <td>{{ $message->sender?->name ?? '—' }}</td>
                                        <td>{{ $message->recipient?->name ?? '—' }}</td>
                                        <td class="f-light">{{ $message->person?->name ?? '—' }}</td>
                                        <td>
                                            @if($message->isRead())
                                                <span class="badge badge-light-success">Read</span>
                                            @else
                                                <span class="badge badge-light-warning">Unread</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-link btn-icon btn-sm" title="View"
                                               href="{{ route('messages.show', $message->id) }}"><i data-feather="eye"></i></a>
                                            @can('messages.delete')
                                            <form class="d-inline" method="POST" action="{{ route('messages.destroy', $message->id) }}"
                                                  onsubmit="return confirm('Delete this message? This cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-icon btn-sm txt-danger" type="submit" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td class="text-center py-4" colspan="7">No messages match these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('partials.pagination', ['paginator' => $messages, 'noun' => 'message'])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
