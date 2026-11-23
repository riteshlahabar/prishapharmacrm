{{--
    The panel landing screen. One template for every role: the service decides
    which cards the viewer may see, by asking each module's own policy, so
    there is no role test anywhere in here (docs/modules/dashboard.md).
--}}
@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6"><h3>Dashboard</h3></div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('partials.flash')

                {{-- Period selector. Only `days` is in the URL, so these links
                     carry no other state to lose. --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <span class="f-light">
                            {{ $data['from']->isoFormat('D MMM') }} – {{ $data['to']->isoFormat('D MMM YYYY') }}
                            @if($data['regions']->isNotEmpty())
                                · <span style="text-transform: none">{{ $data['regions']->pluck('name')->join(', ') }}</span>
                            @elseif(! auth()->user()->isAdmin())
                                · no regions assigned yet
                            @endif
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Period">
                        @foreach($data['periods'] as $period)
                            <a class="btn btn-{{ $data['days'] === $period ? 'primary' : 'outline-primary' }}"
                               href="{{ route('dashboard', ['days' => $period]) }}">{{ $period }} days</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @include('dashboard::partials.tiles', ['tiles' => $data['tiles']])

        @if($data['empty'])
        <div class="row mt-3">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i data-feather="bar-chart-2" style="width:40px;height:40px"></i>
                        <h5 class="mt-3">Nothing to chart yet</h5>
                        <p class="f-light mb-0">
                            No visits or calls were recorded in this period.
                            @unless(auth()->user()->isAdmin())
                                Only your own regions are counted here.
                            @endunless
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="row mt-3">
            @if($data['charts']['series'] !== [])
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5>Activity</h5>
                        <span class="f-light">Per day over the last {{ $data['days'] }} days</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-activity"></div>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($data['charts']['calls_by_status']))
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5>Calls by status</h5>
                        <span class="f-light">{{ $data['charts']['calls_by_status']['total'] }} call(s) in the period</span>
                    </div>
                    <div class="card-body">
                        @if($data['charts']['calls_by_status']['total'] > 0)
                            <div id="chart-calls-status"></div>
                        @else
                            <p class="f-light text-center my-4">No calls logged in this period.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if(isset($data['charts']['attendance']))
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5>Reps present</h5>
                        <span class="f-light">Distinct reps who checked in, per day</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-attendance"></div>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($data['charts']['visits_by_mr']) && $data['charts']['visits_by_mr']['labels'] !== [])
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5>Visits by rep</h5>
                        <span class="f-light">Busiest first, top {{ count($data['charts']['visits_by_mr']['labels']) }}</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-visits-mr"></div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
{{-- ApexCharts 3.46.0, committed with the theme. Cuba's own
     chart/apex-chart/chart-custom.js is demo data bound to demo element ids,
     so it is deliberately not loaded here. --}}
<script src="{{ asset('assets/js/chart/apex-chart/apex-chart.js') }}"></script>
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var chartData = @json($data['charts']);
        var axis = { labels: { style: { colors: '#7f8c9b' } } };

        // Dates are the x axis on every time chart; format once, here.
        var dayAxis = Object.assign({}, axis, {
            type: 'category',
            categories: chartData.labels,
            tickAmount: 8,
            labels: {
                style: { colors: '#7f8c9b' },
                formatter: function (value) {
                    var d = new Date(value);
                    return isNaN(d) ? value : d.getDate() + ' ' +
                        ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()];
                }
            }
        });

        // Counts are whole numbers. Left to itself Apex picks fractional
        // steps on a low-valued chart — a max of 1 over four ticks gives
        // 0.5s, which the rounding formatter then prints as duplicate
        // labels (2, 2, 1, 1, 0). So derive the tick count from the data and
        // raise the top to a clean multiple of it.
        function intYAxis() {
            var max = 0;

            Array.prototype.slice.call(arguments).forEach(function (values) {
                (values || []).forEach(function (v) { if (v > max) { max = v; } });
            });

            var ticks = Math.max(1, Math.min(4, max));
            var top = Math.max(1, Math.ceil(max / ticks) * ticks);

            return {
                min: 0,
                max: top,
                tickAmount: ticks,
                labels: { style: { colors: '#7f8c9b' }, formatter: function (v) { return Math.round(v); } }
            };
        }

        function draw(id, options) {
            var el = document.querySelector(id);

            if (el) {
                new ApexCharts(el, options).render();
            }
        }

        if (chartData.series && chartData.series.length) {
            draw('#chart-activity', {
                series: chartData.series,
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                colors: ['#00797C', '#6EAE40'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                xaxis: dayAxis,
                yaxis: intYAxis.apply(null, chartData.series.map(function (s) { return s.data; })),
                legend: { position: 'top', horizontalAlign: 'right' },
                grid: { borderColor: '#E4ECE7' },
                tooltip: { x: { format: 'dd MMM yyyy' } }
            });
        }

        if (chartData.calls_by_status && chartData.calls_by_status.total > 0) {
            draw('#chart-calls-status', {
                series: chartData.calls_by_status.data,
                labels: chartData.calls_by_status.labels,
                colors: chartData.calls_by_status.colours,
                chart: { type: 'donut', height: 320 },
                dataLabels: { enabled: false },
                legend: { position: 'bottom' },
                plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Calls' } } } } }
            });
        }

        if (chartData.attendance) {
            draw('#chart-attendance', {
                series: [chartData.attendance],
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                colors: ['#6EAE40'],
                dataLabels: { enabled: false },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                xaxis: dayAxis,
                yaxis: intYAxis(chartData.attendance.data),
                grid: { borderColor: '#E4ECE7' }
            });
        }

        if (chartData.visits_by_mr && chartData.visits_by_mr.labels.length) {
            draw('#chart-visits-mr', {
                series: [{ name: 'Visits', data: chartData.visits_by_mr.data }],
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                colors: ['#00797C'],
                dataLabels: { enabled: true },
                plotOptions: { bar: { horizontal: true, borderRadius: 2, barHeight: '60%' } },
                xaxis: Object.assign({}, axis, { categories: chartData.visits_by_mr.labels }),
                grid: { borderColor: '#E4ECE7' }
            });
        }
    })();
</script>
@endsection
