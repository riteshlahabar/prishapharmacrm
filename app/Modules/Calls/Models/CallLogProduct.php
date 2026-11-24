<?php

namespace App\Modules\Calls\Models;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product line on a call log: which product, how many boxes, and what a
 * box cost on the day. The call-side twin of VisitProduct.
 */
#[Fillable(['call_log_id', 'product_id', 'box_qty', 'unit_cost'])]
class CallLogProduct extends Model
{
    protected $table = 'call_log_products';

    protected function casts(): array
    {
        return [
            'box_qty' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
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

    public function productName(): string
    {
        return $this->product?->name ?? 'Product #'.$this->product_id;
    }
}
