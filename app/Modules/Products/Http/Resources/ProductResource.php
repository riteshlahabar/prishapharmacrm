<?php

namespace App\Modules\Products\Http\Resources;

use App\Modules\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The product shape both Flutter apps decode for their dropdowns.
 *
 * `cost` goes out as a string, the way the decimal column holds it: a
 * price that round-trips through a float is a price that can come back as
 * 119.99999.
 *
 * @property Product $resource
 */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'cost' => (string) $this->resource->cost,
            'box_qty' => $this->resource->box_qty,
            'is_active' => $this->resource->is_active,
        ];
    }
}
