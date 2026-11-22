<?php

namespace App\Modules\Persons\Services\Contracts;

use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Business rules for the contact book: who a person is, where they sit, and
 * which MR and telecaller own the relationship.
 */
interface PersonServiceInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** Same list, clamped to the regions this user covers (telecaller side). */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** How many persons this user may see, clamped like the list. */
    public function countForUser(User $user, array $filters = []): int;

    public function find(int|string $id): Person;

    public function create(array $data): Person;

    public function update(int|string $id, array $data): Person;

    public function delete(int|string $id): bool;

    public function toggleActive(int|string $id): bool;
}
