<?php

namespace App\Modules\Persons\Services;

use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use App\Modules\Persons\Exceptions\AppClientNotPermitted;
use App\Modules\Persons\Models\Person;
use App\Modules\Persons\Repositories\Contracts\PersonRepositoryInterface;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class PersonService implements PersonServiceInterface
{
    public function __construct(
        private readonly PersonRepositoryInterface $persons,
        private readonly RegionAssignmentServiceInterface $assignment,
        private readonly ClientTypeServiceInterface $types,
        // Cross-module, through the contract: the region of a client added
        // from an app follows the MR it is assigned to, and only the Users
        // module knows where an MR works (docs/conventions.md).
        private readonly UserServiceInterface $users,
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

    /**
     * "My clients" for either app: the rows this user is an assignee on.
     *
     * The clamp is ownership rather than territory, and that is the point —
     * a telecaller may be handed a client in a region they do not cover, and
     * `paginateForUser`'s region scope would hide it. An admin has no app
     * token and so never reaches this; were one to, they fall back to the
     * territory clamp rather than silently getting an unscoped list.
     */
    public function paginateAssignedTo(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $column = match (true) {
            $user->isMr() => 'assigned_mr_id',
            $user->isTelecaller() => 'assigned_telecaller_id',
            default => null,
        };

        if ($column === null) {
            return $this->paginateForUser($user, $filters, $perPage);
        }

        $filters[$column] = $user->id;

        return $this->paginate($filters, $perPage);
    }

    /**
     * How many persons this user may see. Same clamp as the list, so a tile
     * and the screen it links to can never disagree.
     */
    public function countForUser(User $user, array $filters = []): int
    {
        if (! $user->isAdmin()) {
            $filters['region_scope'] = $this->assignment->idsFor($user);
        }

        return $this->persons->countMatching($filters);
    }

    /**
     * Active persons assigned to each rep, clamped to this viewer. The
     * denominator of the MR report's coverage figure.
     */
    public function activeCountsByMrForUser(User $user, array $filters = []): array
    {
        if (! $user->isAdmin()) {
            $filters['region_scope'] = $this->assignment->idsFor($user);
        }

        return $this->persons->activeCountsByMr($filters);
    }

    public function find(int|string $id): Person
    {
        /** @var Person */
        return $this->persons->findOrFail($id);
    }

    public function findForUser(User $user, int|string $id): Person
    {
        $person = $this->find($id);

        // The rule itself lives in PersonPolicy, so the panel and the API
        // cannot drift apart; what differs is only how a denial is reported.
        if (! Gate::forUser($user)->allows('view', $person)) {
            throw new ModelNotFoundException;
        }

        return $person;
    }

    public function create(array $data): Person
    {
        /** @var Person */
        return $this->persons->create($this->attributes($data));
    }

    /**
     * Add Client, from the MR or the telecaller app.
     *
     * Three things the device does not get to decide, settled here rather
     * than in the request so neither app can forget one:
     *
     *  - **the caller's own assignment** comes from the token, the same rule
     *    as `mr_id` on a visit and `telecaller_id` on a call log;
     *  - **the region** follows the assigned MR, who is the one with a
     *    territory (`users.region_id`); a telecaller covers several, so
     *    theirs would not identify one. That also keeps the new client
     *    inside the territory scope every panel list and report already
     *    uses — a client filed nowhere would be invisible to the admin.
     *  - **is_active** is true: a client someone just added is a client to
     *    work today.
     */
    public function createForApp(User $creator, array $data): Person
    {
        $data = match (true) {
            $creator->isMr() => [...$data, 'assigned_mr_id' => $creator->id],
            $creator->isTelecaller() => [...$data, 'assigned_telecaller_id' => $creator->id],
            default => throw AppClientNotPermitted::forRole($creator->role?->value),
        };

        $data['region_id'] = $this->regionOfMr($data['assigned_mr_id'] ?? null);
        $data['is_active'] = true;

        return $this->create($data);
    }

    /**
     * Edit Client, from the MR or the telecaller app.
     *
     * Only a client this caller is an assignee on, and anyone else's is
     * reported **missing** rather than forbidden — the same rule as
     * `findForUser` and a visit amendment, so the app cannot be used to
     * confirm that a client it may not touch exists.
     *
     * A partial write, unlike `createForApp`: the keys the body carried are
     * merged onto the row and nothing else moves. The region in particular
     * stays where it is, because neither app may change the side it is
     * derived from — an MR edits the telecaller, a telecaller edits the MR,
     * and only the panel moves a client between reps.
     *
     * @param  array<string, mixed>  $data  Only the fields being changed.
     */
    public function updateForApp(User $editor, int|string $id, array $data): Person
    {
        $person = $this->editableByApp($editor, $id);

        if ($data === []) {
            return $person;
        }

        // A type change re-decides whether the row carries a specialization
        // at all, so it is settled against the type being saved rather than
        // the one on the record.
        if (array_key_exists('type', $data) || array_key_exists('specialization', $data)) {
            $slug = (string) ($data['type'] ?? $person->type);

            $data['specialization'] = $this->types->findBySlug($slug)?->has_specialization
                ? ($data['specialization'] ?? $person->specialization)
                : null;
        }

        /** @var Person */
        return $this->persons->update($person->id, $data);
    }

    /**
     * The caller's own client, or a 404.
     *
     * Ownership, not territory — the same clamp as `paginateAssignedTo`, so
     * every row My Clients shows is a row its edit button can save, which is
     * the whole point of a list with a pencil on it.
     */
    private function editableByApp(User $editor, int|string $id): Person
    {
        $column = match (true) {
            $editor->isMr() => 'assigned_mr_id',
            $editor->isTelecaller() => 'assigned_telecaller_id',
            default => throw AppClientNotPermitted::forRole($editor->role?->value),
        };

        $person = $this->find($id);

        if ((int) $person->{$column} !== (int) $editor->id) {
            throw new ModelNotFoundException;
        }

        return $person;
    }

    /**
     * Where an MR works, or null if they have no territory yet.
     *
     * A missing region is left as null rather than guessed: the client still
     * belongs to the two people named on it, and an admin assigning the MR a
     * region later is what fills it in.
     */
    private function regionOfMr(int|string|null $mrId): ?int
    {
        if ($mrId === null) {
            return null;
        }

        return $this->users->findForRole(UserRole::Mr, $mrId)->region_id;
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
        $slug = (string) $data['type'];
        // Unknown slugs cannot reach here — StorePersonRequest checks the
        // master — but a type can be deactivated between form and submit,
        // and that must not silently drop the specialization rules.
        $carriesSpecialization = (bool) $this->types->findBySlug($slug)?->has_specialization;

        return [
            'name' => $data['name'],
            'type' => $slug,
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            // Only a type flagged for it has one; keeping it on the others
            // would leave stale text behind after a type change.
            'specialization' => $carriesSpecialization ? ($data['specialization'] ?? null) : null,
            'region_id' => $data['region_id'] ?? null,
            'assigned_mr_id' => $data['assigned_mr_id'] ?? null,
            'assigned_telecaller_id' => $data['assigned_telecaller_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
