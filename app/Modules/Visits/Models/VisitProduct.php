<?php

namespace App\Modules\Visits\Models;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product line on a visit: which product, how many boxes, and what a box
 * cost on the day (see the create migration for why that is stored).
 */
#[Fillable(['visit_id', 'product_id', 'box_qty', 'unit_cost'])]
class VisitProduct extends Model
{
    protected $table = 'visit_products';

    protected function casts(): array
    {
        return [
            'box_qty' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Boxes × the cost that was quoted, not today's. */
    public function lineTotal(): float
    {
        return round($this->box_qty * (float) $this->unit_cost, 2);
    }

    /** The product's name as it is now, or a placeholder if it is gone. */
    public function productName(): string
    {
        return $this->product?->name ?? 'Product #'.$this->product_id;
    }
}
