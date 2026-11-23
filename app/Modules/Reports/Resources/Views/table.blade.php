{{-- Every column-based report renders here. The column list comes from
     ReportDefinition, so the table head, the sort links and the CSV all read
     from one place and cannot drift apart. --}}
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
                        <li class="breadcrumb-item active">{{ $definition['title'] }}</li>
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
                            ({{ $filters['days'] }} days)
                            @unless(auth()->user()->isAdmin()) · your regions only @endunless
                        </span>
                    </div>

                    <div class="card-body">
                        {{-- A total is named by the column it totals when there
                             is one, so a card and the column head cannot
                             disagree (e.g. "Clients seen", not "Persons
                             seen"). --}}
                        @php
                            $accents = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
                            $totalStats = [];

                            foreach (array_values((array) $data['totals']) as $i => $value) {
                                $key = array_keys((array) $data['totals'])[$i];

                                $totalStats[] = [
                                    'label' => $definition['columns'][$key]['label']
                                        ?? ucfirst(str_replace('_', ' ', $key)),
                                    'value' => $value,
                                    'colour' => $accents[$i % count($accents)],
                                ];
                            }
                        @endphp

                        @include('partials.stat-chips', ['stats' => $totalStats])

                        @include('reports::_filters')

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        @foreach($definition['columns'] as $key => $column)
                                            <th class="{{ ($column['numeric'] ?? false) ? 'text-end' : '' }}">
                                                <a class="text-dark"
                                                   href="{{ route('reports.show', array_merge(['report' => $slug], request()->query(), ['sort' => $key])) }}">
                                                    {{ $column['label'] }}
                                                    @if(($filters['sort'] ?? '') === $key)
                                                        <i data-feather="chevron-down" style="width:14px;height:14px"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['rows'] as $row)
                                    <tr>
                                        @foreach($definition['columns'] as $key => $column)
                                            @php $value = $row[$key] ?? null; @endphp
                                            <td class="{{ ($column['numeric'] ?? false) ? 'text-end' : '' }}">
                                                @if($value === null)
                                                    <span class="f-light">—</span>
                                                @elseif($loop->first)
                                                    <span class="fw-bold">{{ $value }}</span>
                                                    @if($slug === 'coverage' && ($row['never_touched'] ?? false))
                                                        <span class="badge badge-light-danger ms-1">never touched</span>
                                                    @endif
                                                @else
                                                    {{ $value }}{{ $column['suffix'] ?? '' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center py-4" colspan="{{ max(count($definition['columns']), 1) }}">
                                            @if($slug === 'coverage')
                                                Nothing neglected — every contact in scope was visited or called inside the window.
                                            @else
                                                No data for this period.
                                            @endif
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($slug === 'coverage')
                        <p class="f-light mt-3 mb-0">
                            Scanned {{ $data['totals']['scanned'] }} of {{ $data['totals']['total'] }} active contacts
                            in scope; showing those untouched for {{ $data['stale_days'] }} days or more.
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
