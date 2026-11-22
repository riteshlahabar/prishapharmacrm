<?php

namespace App\Modules\Persons\Policies;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Models\User;

/**
 * Who may touch a person. Both apps read this list, so `viewAny` admits all
 * three roles and the row scope is the caller's territory — which is what
 * PersonService already clamps every list to.
 *
 * Writing is panel work: the admin maintains the master list.
 */
class PersonPolicy
{
    public function __construct(private readonly RegionAssignmentServiceInterface $assignment) {}

    public function viewAny(User $user): bool
    {
        return $user->canSignIn()
            && ($user->isMr() || $user->isTelecaller() || $user->isAdmin() || $user->can('persons.view'));
    }

    public function view(User $user, Person $person): bool
    {
        if (! $user->canSignIn()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $person->region_id !== null
            && in_array($person->region_id, $this->assignment->idsFor($user), false);
    }

    public function create(User $user): bool
    {
        return $user->canSignIn() && $user->can('persons.create');
    }

    public function update(User $user, Person $person): bool
    {
        return $user->canSignIn() && $user->can('persons.update');
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->canSignIn() && $user->can('persons.delete');
    }
}
