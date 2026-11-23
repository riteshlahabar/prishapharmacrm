{{--
    A captured coordinate, linked out to a map. Shows nothing at all when the
    device sent no fix, so a blank cell reads as "no location", not "zero".
--}}
@if($lat !== null && $lng !== null)
    <a class="map-pin ms-1" target="_blank" rel="noopener noreferrer"
       title="{{ $lat }}, {{ $lng }}"
       href="https://www.google.com/maps/search/?api=1&query={{ $lat }},{{ $lng }}">
        <i data-feather="map-pin"></i>
    </a>
@endif
