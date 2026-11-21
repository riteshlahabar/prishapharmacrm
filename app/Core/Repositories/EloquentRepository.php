<?php

namespace App\Core\Repositories;

use App\Core\Contracts\Filterable;
use App\Core\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared Eloquent implementation. Modules extend this and declare $model only,
 * overriding applyFilters() when they need module-specific filtering (OCP).
 */
abstract class EloquentRepository implements Filterable, RepositoryInterface
{
    abstract protected function model(): Model;

    public function query(): Builder
    {
        return $this->model()->newQuery();
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(int|string $id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->update($attributes);

        return $record->refresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query;
    }
}
