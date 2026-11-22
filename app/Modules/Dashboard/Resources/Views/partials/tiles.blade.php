{{--
    Headline numbers. Every entry arrives ready to render — label, value,
    sub-line, feather icon and the route behind it — so this file decides
    nothing (app/Modules/Dashboard/Services/DashboardService.php).
--}}
<div class="row g-3">
    @foreach($tiles as $tile)
    <div class="col-md-4 col-xl-2">
        <a class="text-decoration-none" href="{{ route($tile['route']) }}">
            <div class="card h-100 mb-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <span class="f-light f-12 d-block">{{ $tile['label'] }}</span>
                            <h4 class="mt-1 mb-0">{{ $tile['value'] }}</h4>
                        </div>
                        <i class="ms-2" data-feather="{{ $tile['icon'] }}"></i>
                    </div>
                    <span class="f-light f-12 d-block mt-2">{{ $tile['sub'] }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
