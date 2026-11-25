<?php

namespace App\Modules\Visits\Policies;

use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Models\Visit;

/**
 * Who may touch a visit. One rule, two renderings:
 *
 * - the panel calls these through `authorize()`, so a denial is a **403**;
 * - the mobile API routes a row denial through the service, which raises a
 *   **404** instead, because confirming the row exists would already leak
 *   that some MR called on that person (docs/api/v1-common.md).
 *
 * An MR holds no panel permissions at all — `mr` is app-only — so a role
 * check stands beside each permission check rather than behind it.
 */
class VisitPolicy
{
    public function __construct(private readonly RegionAssignmentServiceInterface $assignment) {}

    public function viewAny(User $user): bool
    {
        return $user->canSignIn() && ($user->isMr() || $user->isAdmin() || $user->can('visits.view'));
    }

    /**
     * Territory, not authorship: Phase 3 settled that anyone covering the
     * region may read a visit filed there, so a second MR on the same patch
     * sees it too. An admin covers everything.
     */
    public function view(User $user, Visit $visit): bool
    {
        if (! $user->canSignIn()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->covers($user, $visit);
    }

    /** Filing is the field rep's job; the panel needs the permission for it. */
    public function create(User $user): bool
    {
        return $user->canSignIn() && ($user->isMr() || $user->can('visits.create'));
    }

    public function update(User $user, Visit $visit): bool
    {
        return $user->canSignIn()
            && $user->can('visits.update')
            && ($user->isAdmin() || $this->covers($user, $visit));
    }

    /**
     * Correcting an entry from the app that filed it. Authorship, not
     * territory and not a panel permission: the rep fixes a purpose they
     * picked wrong or a clock they guessed, and a second MR on the same
     * patch — who may *read* the visit — does not get to rewrite it.
     *
     * No admin bypass, as on `CallLogPolicy::update`: the panel reads a
     * visit for reporting and deletes a mistaken one, but does not edit
     * what a rep reported.
     */
    public function amend(User $user, Visit $visit): bool
    {
        return $user->canSignIn()
            && $user->isMr()
            && (int) $visit->mr_id === (int) $user->id;
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->canSignIn()
            && $user->can('visits.delete')
            && ($user->isAdmin() || $this->covers($user, $visit));
    }

    /** A visit sits in a region through the person it was filed against. */
    private function covers(User $user, Visit $visit): bool
    {
        $regionId = $visit->person?->region_id;

        return $regionId !== null
            && in_array($regionId, $this->assignment->idsFor($user), false);
    }
}
