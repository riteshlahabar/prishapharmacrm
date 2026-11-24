{{--
    The products recorded on a visit or a call.

        @include('partials.product-lines', ['lines' => $visit->products])

    Each line carries the cost of a box *on the day it was recorded*, which
    is why the total here can differ from the product's price today — that is
    the point of storing it (see the visit_products migration).
--}}
@php
    $lines = collect($lines ?? []);
    $total = $lines->sum(fn ($line) => $line->lineTotal());
@endphp

<div class="card">
    <div class="card-header">
        <h5>Products</h5>
        <span class="f-light">
            @if($lines->isEmpty())
                Nothing was recorded on this one.
            @else
                {{ $lines->count() }} line(s) · costs are the ones quoted at the time
            @endif
        </span>
    </div>

    @if($lines->isNotEmpty())
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Box Qty</th>
                        <th class="text-end">Cost / Box</th>
                        <th class="text-end">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                    <tr>
                        <td>{{ $line->productName() }}</td>
                        <td class="text-end">{{ number_format($line->box_qty) }}</td>
                        <td class="text-end">₹{{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($line->lineTotal(), 2) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end fw-bold">₹{{ number_format($total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
