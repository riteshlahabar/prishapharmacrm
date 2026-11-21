<?php

namespace App\Modules\Users\Services\Contracts;

use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Business rules for accounts: who may exist, with what role, and whether they
 * may sign in. Controllers and other modules depend on this contract only.
 */
interface UserServiceInterface
{
    public function paginateForRole(UserRole $role, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findForRole(UserRole $role, int|string $id): User;

    /** Create an account in the given role; `password` is hashed by the model. */
    public function createInRole(UserRole $role, array $data): User;

    /** Update an account; an empty `password` leaves the current one in place. */
    public function updateInRole(UserRole $role, int|string $id, array $data): User;

    public function delete(UserRole $role, int|string $id): bool;

    /** Flip is_active; returns the new state. */
    public function toggleActive(UserRole $role, int|string $id): bool;
}
