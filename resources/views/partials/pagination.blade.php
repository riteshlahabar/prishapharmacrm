{{--
    The row under a table: how many rows the viewer is looking at on the left,
    the pager on the right, stacked on a phone (.table-foot in prisha.css).

        @include('partials.pagination', ['paginator' => $clients, 'noun' => 'client'])

    `withQueryString()` is the point of having this in one place: without it
    page 2 of a filtered list drops every filter, and eight index views each
    had their own bare `->links()` call to forget it in.
--}}
@php
    $noun = $noun ?? 'row';
    $total = $paginator->total();
@endphp

<div class="table-foot">
    <span class="pagination-summary">
        @if ($total > 0)
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ number_format($total) }} {{ \Illuminate\Support\Str::plural($noun, $total) }}
        @else
            No {{ \Illuminate\Support\Str::plural($noun) }} to show
        @endif
    </span>

    {{ $paginator->withQueryString()->links() }}
</div>
