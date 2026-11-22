<?php

namespace App\Modules\Users\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Persistence for accounts. Services depend on this, never on Eloquent (DIP).
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /** Paginate users of one role, honouring search/active filters. */
    public function paginateByRole(UserRole $role, int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /** A single user of that role, or 404 — stops /mrs/{id} reaching a telecaller. */
    public function findOfRoleOrFail(UserRole $role, int|string $id): User;

    /** @return Collection<int, User> */
    public function activeOfRole(UserRole $role): Collection;

    /**
     * Active users of a role whose own region is one of these. An empty list
     * means no territory, and so no users — never "all of them".
     *
     * @param  array<int, int>  $regionIds
     * @return Collection<int, User>
     */
    public function activeOfRoleInRegions(UserRole $role, array $regionIds): Collection;

    public function findByEmail(string $email): ?User;
}
