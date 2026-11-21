<?php

namespace App\Modules\Persons\Services;

use App\Modules\Persons\Enums\PersonType;
use App\Modules\Persons\Models\Person;
use App\Modules\Persons\Repositories\Contracts\PersonRepositoryInterface;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PersonService implements PersonServiceInterface
{
    public function __construct(
        private readonly PersonRepositoryInterface $persons,
        private readonly RegionAssignmentServiceInterface $assignment,
    ) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->persons->paginateWithRelations($perPage, $filters);
    }

    /**
     * An admin sees everything; anyone else sees only their own regions. The
     * scope is applied here rather than in the controller so no caller can
     * forget it.
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        if (! $user->isAdmin()) {
            $filters['region_scope'] = $this->assignment->idsFor($user);
        }

        return $this->paginate($filters, $perPage);
    }

    public function find(int|string $id): Person
    {
        /** @var Person */
        return $this->persons->findOrFail($id);
    }

    public function create(array $data): Person
    {
        /** @var Person */
        return $this->persons->create($this->attributes($data));
    }

    public function update(int|string $id, array $data): Person
    {
        /** @var Person */
        return $this->persons->update($id, $this->attributes($data));
    }

    public function delete(int|string $id): bool
    {
        return $this->persons->delete($id);
    }

    public function toggleActive(int|string $id): bool
    {
        $person = $this->find($id);
        $person->is_active = ! $person->is_active;
        $person->save();

        return $person->is_active;
    }

    private function attributes(array $data): array
    {
        $type = $data['type'] instanceof PersonType
            ? $data['type']
            : PersonType::from((string) $data['type']);

        return [
            'name' => $data['name'],
            'type' => $type,
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            // Only a doctor has one; keeping it on the others would leave
            // stale text behind after a type change.
            'specialization' => $type->hasSpecialization() ? ($data['specialization'] ?? null) : null,
            'region_id' => $data['region_id'] ?? null,
            'assigned_mr_id' => $data['assigned_mr_id'] ?? null,
            'assigned_telecaller_id' => $data['assigned_telecaller_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
