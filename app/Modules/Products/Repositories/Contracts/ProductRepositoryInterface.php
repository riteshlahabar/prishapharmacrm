<?php

namespace App\Modules\Products\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface extends RepositoryInterface
{
    /** Active products, by name — what both apps and every picker show. */
    public function activeOptions(): Collection;

    /** @param  array<int, int>  $ids */
    public function findManyById(array $ids): Collection;

    /** How many visit and call lines name this product. */
    public function usageCount(int $productId): int;
}
