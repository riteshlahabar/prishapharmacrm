<?php

namespace App\Modules\Users\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Every `users` query in the application lives here (docs/conventions.md).
 */
class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    protected function model(): Model
    {
        return new User;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role'] instanceof UserRole
                ? $filters['role']->value
                : $filters['role']);
        }

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $query->where(function (Builder $q) use ($search) {
                $like = "%{$search}%";
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('mobile', 'like', $like);
            });
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        if (! empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }

        return $query;
    }

    public function paginateByRole(UserRole $role, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate($perPage, array_merge($filters, ['role' => $role]));
    }

    public function findOfRoleOrFail(UserRole $role, int|string $id): User
    {
        return $this->query()
            ->where('role', $role->value)
            ->findOrFail($id);
    }

    public function activeOfRole(UserRole $role): Collection
    {
        return $this->query()
            ->where('role', $role->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }
}
