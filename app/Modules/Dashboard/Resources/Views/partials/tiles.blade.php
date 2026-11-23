{{--
    Headline numbers. Every entry arrives ready to render — label, value,
    sub-line, feather icon, accent colour and the route behind it — so this
    file decides nothing (app/Modules/Dashboard/Services/DashboardService.php).

    The accent is a class name, not a hex: the gradients live in
    assets/css/prisha.css next to the rest of the brand layer.
--}}
<div class="row g-3">
    @foreach($tiles as $tile)
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <a class="text-decoration-none" href="{{ route($tile['route']) }}">
            <div class="card stat-tile tile-{{ $tile['colour'] ?? 'teal' }} h-100 mb-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <span class="stat-label f-12 d-block">{{ $tile['label'] }}</span>
                            <h4 class="stat-value mt-1 mb-0">{{ $tile['value'] }}</h4>
                        </div>
                        <span class="stat-icon ms-2"><i data-feather="{{ $tile['icon'] }}"></i></span>
                    </div>
                    <span class="stat-sub f-12 d-block mt-2">{{ $tile['sub'] }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
