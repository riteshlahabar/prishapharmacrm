{{--
    Message detail. Shows who wrote to whom, the context they attached, and
    whether the rep has read it yet.
--}}
@extends('layouts.master')

@section('title', 'Message')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Message</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('messages.index') }}">Messages</a></li>
                        <li class="breadcrumb-item active">{{ Str::limit($message->subject, 30) }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                @include('partials.flash')

                <div class="card">
                    <div class="card-header">
                        {{-- Cuba title-cases a card-header h5, which would re-case
                             what the telecaller actually typed. Its `:not(.mb-0)`
                             escape hatch does not work — the rule is a selector
                             list, so the other half still matches (ui-cuba.md). --}}
                        <h5 style="text-transform: none">{{ $message->subject }}</h5>
                        {{-- Wrapped: Cuba styles a direct span child of .card-header
                             as a block-level subtitle (docs/ui-cuba.md). --}}
                        <div class="mt-1">
                            @if($message->isRead())
                                <span class="badge badge-light-success">{{ $message->readLabel() }}</span>
                            @else
                                <span class="badge badge-light-warning">Unread</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordernone">
                                <tbody>
                                    <tr><th class="w-25">From</th><td>{{ $message->sender?->name ?? '—' }}</td></tr>
                                    <tr><th>To</th><td>{{ $message->recipient?->name ?? '—' }}</td></tr>
                                    <tr><th>Sent</th><td>{{ $message->created_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
                                    <tr>
                                        <th>About</th>
                                        <td>
                                            @if($message->person)
                                                @can('persons.view')
                                                    <a href="{{ route('persons.show', $message->person->id) }}">{{ $message->person->name }}</a>
                                                @else
                                                    {{ $message->person->name }}
                                                @endcan
                                                <span class="badge {{ $message->person->type?->badgeClass() }} ms-1">
                                                    {{ $message->person->typeLabel() }}
                                                </span>
                                            @else
                                                No person attached
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Call</th>
                                        <td>
                                            @if($message->call_log_id)
                                                @can('calls.view')
                                                    <a href="{{ route('calls.show', $message->call_log_id) }}">
                                                        Call #{{ $message->call_log_id }}
                                                    </a>
                                                @else
                                                    Call #{{ $message->call_log_id }}
                                                @endcan
                                            @else
                                                No call attached
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h6 class="f-light">Message</h6>
                            <p class="mb-0" style="white-space: pre-wrap">{{ $message->body }}</p>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a class="btn btn-light" href="{{ route('messages.index') }}">Back to Messages</a>
                        @can('messages.delete')
                        <form class="d-inline" method="POST" action="{{ route('messages.destroy', $message->id) }}"
                              onsubmit="return confirm('Delete this message? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger ms-2" type="submit">Delete message</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
