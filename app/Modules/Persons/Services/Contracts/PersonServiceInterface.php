<?php

namespace App\Modules\Persons\Services\Contracts;

use App\Modules\Persons\Exceptions\AppClientNotPermitted;
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

    /**
     * "My clients": the ones this user is the assigned MR or telecaller of.
     *
     * Ownership, not territory — deliberately a different clamp from
     * `paginateForUser`. A client added in one app is assigned to a
     * counterpart who may cover another region, and that person must still
     * see it; a region clamp would hide exactly the rows this list exists to
     * show (docs/api/v1-{mr,telecaller}.md).
     */
    public function paginateAssignedTo(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** How many persons this user may see, clamped like the list. */
    public function countForUser(User $user, array $filters = []): int;

    /**
     * Active persons assigned to each rep, clamped to this viewer:
     * `assigned_mr_id => count`.
     *
     * @return array<int, int>
     */
    public function activeCountsByMrForUser(User $user, array $filters = []): array;

    public function find(int|string $id): Person;

    /**
     * One person, but only if this user may see them. PersonPolicy::view holds
     * the rule (admin, or the person's region is one the user covers); a
     * denial reads as missing rather than forbidden, so the panel cannot be
     * used to confirm that a person outside your territory exists.
     */
    public function findForUser(User $user, int|string $id): Person;

    public function create(array $data): Person;

    /**
     * Add Client from the MR or telecaller app.
     *
     * The caller is one of the two assignees by definition, so their own id
     * comes from the token and never from the body; the counterpart comes
     * from the form. The region follows the MR, who is the one with a
     * territory.
     *
     * @throws AppClientNotPermitted when the caller is neither an MR nor a
     *                               telecaller.
     */
    public function createForApp(User $creator, array $data): Person;

    public function update(int|string $id, array $data): Person;

    public function delete(int|string $id): bool;

    public function toggleActive(int|string $id): bool;
}
