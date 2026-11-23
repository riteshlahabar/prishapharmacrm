{{--
    The breakdown above a report's filters: one small card per bucket, the
    whole set on a single row.

        @include('partials.stat-chips', ['stats' => [
            ['label' => 'Introduction', 'value' => 9, 'colour' => 'primary'],
        ]])

    `colour` is a Cuba badge name (primary, success, info, warning, danger,
    secondary) and may be left out — `.mini-stats` in prisha.css paints the
    accent bar and the figure from it. The grid fits every card on one row
    while they stay legible and wraps below that, so a seven-bucket
    breakdown does not force a horizontal scrollbar on a laptop.
--}}
@if(($stats ?? []) !== [])
<div class="mini-stats mb-4">
    @foreach($stats as $stat)
        <div class="mini-stat mini-stat-{{ $stat['colour'] ?? 'primary' }}">
            <span class="mini-stat-label">{{ $stat['label'] }}</span>
            <span class="mini-stat-value">{{ $stat['value'] }}</span>
        </div>
    @endforeach
</div>
@endif
