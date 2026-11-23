{{-- Shared report filter bar. The period applies to every report; the person
     filters only matter on the coverage report, so they render only there. --}}
<form class="filter-bar row g-2" method="GET" action="{{ route('reports.show', $slug) }}">
    <div class="col-md-2">
        <label class="form-label f-light" for="date_from">From</label>
        <input class="form-control" id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}">
    </div>
    <div class="col-md-2">
        <label class="form-label f-light" for="date_to">To</label>
        <input class="form-control" id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}">
    </div>

    @if($slug === 'coverage')
    <div class="col-md-2">
        <label class="form-label f-light" for="stale_days">Untouched for</label>
        <select class="form-select" id="stale_days" name="stale_days">
            @foreach([14, 30, 60, 90] as $days)
                <option value="{{ $days }}" @selected((int) ($data['stale_days'] ?? 30) === $days)>{{ $days }} days</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label f-light" for="type">Type</label>
        <select class="form-select" id="type" name="type">
            <option value="">Any type</option>
            @foreach($types as $type)
                <option value="{{ $type->slug }}" @selected(($filters['type'] ?? '') === $type->slug)>{{ $type->label }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="col-md-3">
        <label class="form-label f-light" for="region_id">Region</label>
        <select class="form-select" id="region_id" name="region_id">
            <option value="">{{ auth()->user()->isAdmin() ? 'All regions' : 'All my regions' }}</option>
            @foreach($regionOptions as $region)
                <option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>
                    {{ $region->name }}
                </option>
            @endforeach
        </select>
    </div>

    @if($slug === 'coverage')
    <div class="col-md-3">
        <label class="form-label f-light" for="search">Search</label>
        <input class="form-control" id="search" type="search" name="search"
               value="{{ $filters['search'] ?? '' }}" placeholder="Client name or city">
    </div>
    @endif

    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">

    <div class="col-12 mt-3">
        <button class="btn btn-outline-primary" type="submit">
            <i data-feather="search"></i> Apply
        </button>
        <a class="btn btn-light" href="{{ route('reports.show', $slug) }}">Reset</a>

        @can('reports.export')
        <a class="btn btn-light float-end"
           href="{{ route('reports.export', array_merge(['report' => $slug], request()->query())) }}">
            <i data-feather="download"></i> Download CSV
        </a>
        @endcan
    </div>
</form>
