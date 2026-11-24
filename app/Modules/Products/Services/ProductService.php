<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Exceptions\ProductInUse;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Products\Services\Contracts\ProductServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductService implements ProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->products->paginate($perPage, $filters);
    }

    public function find(int|string $id): Product
    {
        /** @var Product */
        return $this->products->findOrFail($id);
    }

    public function create(array $data): Product
    {
        /** @var Product */
        return $this->products->create($this->attributes($data));
    }

    public function update(int|string $id, array $data): Product
    {
        /** @var Product */
        return $this->products->update($id, $this->attributes($data));
    }

    public function delete(int|string $id): bool
    {
        $product = $this->find($id);
        $lines = $this->products->usageCount($product->id);

        if ($lines > 0) {
            throw ProductInUse::make($product->name, $lines);
        }

        return $this->products->delete($id);
    }

    public function toggleActive(int|string $id): bool
    {
        $product = $this->find($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        return $product->is_active;
    }

    public function activeOptions(): Collection
    {
        return $this->products->activeOptions();
    }

    public function priceLines(array $rows): array
    {
        $wanted = [];

        foreach ($rows as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['box_qty'] ?? 0);

            if ($id > 0 && $qty > 0) {
                $wanted[$id] = ($wanted[$id] ?? 0) + $qty;
            }
        }

        if ($wanted === []) {
            return [];
        }

        $costs = $this->products
            ->findManyById(array_keys($wanted))
            ->mapWithKeys(fn (Product $product) => [$product->id => (string) $product->cost])
            ->all();

        $lines = [];

        foreach ($wanted as $id => $qty) {
            // A product that vanished between the app loading its list and
            // posting: drop the line rather than fail the whole visit.
            if (! array_key_exists($id, $costs)) {
                continue;
            }

            $lines[] = ['product_id' => $id, 'box_qty' => $qty, 'unit_cost' => $costs[$id]];
        }

        return $lines;
    }

    private function attributes(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'box_qty' => max(0, (int) ($data['box_qty'] ?? 0)),
            'cost' => round((float) ($data['cost'] ?? 0), 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
