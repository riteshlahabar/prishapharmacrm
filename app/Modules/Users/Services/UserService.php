<?php

namespace App\Modules\Users\Services;

use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keeps two things in step that are easy to get wrong separately: the
 * `users.role` column (used by the apps and by list filters) and the spatie
 * role assignment (used by every permission check). Nothing else in the app
 * writes a role.
 *
 * Region coverage is delegated to the Regions module through its service
 * contract, so this class never has to know that an MR stores one region on
 * the row while a telecaller stores many in a pivot.
 */
class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RegionAssignmentServiceInterface $regions,
    ) {}

    public function paginateForRole(UserRole $role, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->users->paginateByRole($role, $perPage, $filters);
    }

    public function findForRole(UserRole $role, int|string $id): User
    {
        return $this->users->findOfRoleOrFail($role, $id);
    }

    public function createInRole(UserRole $role, array $data): User
    {
        return DB::transaction(function () use ($role, $data) {
            /** @var User $user */
            $user = $this->users->create($this->attributes($data, $role) + [
                'password' => $data['password'],
            ]);

            $user->syncRoles([$role->value]);
            $this->regions->assign($user, $data['region_ids'] ?? []);

            return $user->refresh();
        });
    }

    public function updateInRole(UserRole $role, int|string $id, array $data): User
    {
        return DB::transaction(function () use ($role, $id, $data) {
            $user = $this->users->findOfRoleOrFail($role, $id);

            $attributes = $this->attributes($data, $role);

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $user->fill($attributes)->save();
            $user->syncRoles([$role->value]);
            $this->regions->assign($user, $data['region_ids'] ?? []);

            return $user->refresh();
        });
    }

    public function delete(UserRole $role, int|string $id): bool
    {
        $user = $this->users->findOfRoleOrFail($role, $id);
        $user->tokens()->delete();

        return (bool) $user->delete();
    }

    public function toggleActive(UserRole $role, int|string $id): bool
    {
        $user = $this->users->findOfRoleOrFail($role, $id);
        $user->is_active = ! $user->is_active;
        $user->save();

        // A disabled account must lose its app session immediately.
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return $user->is_active;
    }

    public function activeOfRoleInRegions(UserRole $role, array $regionIds): Collection
    {
        return $this->users->activeOfRoleInRegions($role, $regionIds);
    }

    /**
     * Whitelist of writable columns. The role always comes from the caller's
     * route, never from the request body, and `region_id` is left out: region
     * coverage goes through RegionAssignmentService, which knows the shape
     * each role uses.
     */
    private function attributes(array $data, UserRole $role): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'role' => $role,
        ];
    }
}
