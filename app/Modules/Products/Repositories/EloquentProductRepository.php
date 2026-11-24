<?php

namespace App\Modules\Products\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every `products` query in the application lives here.
 */
class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    protected function model(): Model
    {
        return new Product;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        if (! empty($filters['in_stock'])) {
            $query->where('box_qty', '>', 0);
        }

        return $query;
    }

    public function activeOptions(): Collection
    {
        return $this->query()->where('is_active', true)->orderBy('name')->get();
    }

    public function findManyById(array $ids): Collection
    {
        return $ids === []
            ? collect()
            : $this->query()->whereIn('id', $ids)->get();
    }

    /**
     * Counted across both line tables in one go. They are owned by Visits and
     * Calls, but a product has to know whether anything points at it before
     * it may be deleted, and asking two sibling services for a per-product
     * count would be the same two queries behind a slower loop.
     */
    public function usageCount(int $productId): int
    {
        $total = 0;

        foreach (['visit_products', 'call_log_products'] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $total += DB::table($table)->where('product_id', $productId)->count();
            }
        }

        return $total;
    }
}
