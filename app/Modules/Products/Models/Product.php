<?php

namespace App\Modules\Products\Models;

use App\Modules\Products\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One medical product: what it is called, how many boxes are on hand, and
 * what a box costs. State only — querying lives in the repository.
 */
#[Fillable(['name', 'box_qty', 'cost', 'is_active'])]
#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'box_qty' => 'integer',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** "Paracetamol 500 (₹120.00)" — what both app dropdowns show. */
    public function label(): string
    {
        return "{$this->name} (₹".number_format((float) $this->cost, 2).')';
    }

    public function isOutOfStock(): bool
    {
        return $this->box_qty <= 0;
    }
}
