<?php

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract every module repository depends on (DIP): services type-hint this,
 * never a concrete Eloquent repository.
 */
interface RepositoryInterface
{
    public function query(): Builder;

    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;
}
