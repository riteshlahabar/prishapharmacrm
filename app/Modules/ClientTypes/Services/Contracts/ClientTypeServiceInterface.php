<?php

namespace App\Modules\ClientTypes\Services\Contracts;

use App\Modules\ClientTypes\Exceptions\ClientTypeInUse;
use App\Modules\ClientTypes\Models\ClientType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The client-type master. Other modules depend on this contract — never on
 * the model or its repository (docs/conventions.md). The Persons module uses
 * it for the form picker, for validation and to decide whether a type carries
 * a specialization.
 */
interface ClientTypeServiceInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(int|string $id): ClientType;

    public function create(array $data): ClientType;

    public function update(int|string $id, array $data): ClientType;

    /** @throws ClientTypeInUse */
    public function delete(int|string $id): bool;

    public function toggleActive(int|string $id): bool;

    /** Active types, ordered — the options every client picker shows. */
    public function activeOptions(): Collection;

    /**
     * The options a client form may show: the active ones, plus the client's
     * own type even if it has since been retired, so editing an old client
     * never silently re-types them.
     */
    public function optionsFor(?string $currentSlug): Collection;

    /** All types, ordered, whatever their state. */
    public function all(): Collection;

    public function findBySlug(string $slug): ?ClientType;

    /** @return array<string, int> slug => client count */
    public function usageCounts(): array;
}
