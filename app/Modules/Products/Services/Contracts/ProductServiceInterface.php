<?php

namespace App\Modules\Products\Services\Contracts;

use App\Modules\Products\Exceptions\ProductInUse;
use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The product master. Visits and Calls depend on this contract — never on
 * the model or its repository (docs/conventions.md) — to price a line and to
 * check that a posted product exists.
 */
interface ProductServiceInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(int|string $id): Product;

    public function create(array $data): Product;

    public function update(int|string $id, array $data): Product;

    /** @throws ProductInUse */
    public function delete(int|string $id): bool;

    public function toggleActive(int|string $id): bool;

    /** Active products, by name: the list both apps download. */
    public function activeOptions(): Collection;

    /**
     * Turn what an app posted — `[['product_id' => 4, 'box_qty' => 2], …]` —
     * into rows ready to store, each carrying the cost of a box **as it is
     * now**. The snapshot is the point: a price change next month must not
     * rewrite the visit that already happened.
     *
     * Pricing is product knowledge, so it lives here rather than being
     * copied into the Visits and Calls services. Unknown products and
     * quantities below one are dropped, and two lines for the same product
     * are summed — tapping + twice for the same box is a quantity, not a
     * second fact, and the line tables are unique per product.
     *
     * @param  array<int, array{product_id: mixed, box_qty: mixed}>  $rows
     * @return array<int, array{product_id: int, box_qty: int, unit_cost: string}>
     */
    public function priceLines(array $rows): array;
}
