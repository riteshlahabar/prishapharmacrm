<?php

namespace App\Modules\ClientTypes\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

interface ClientTypeRepositoryInterface extends RepositoryInterface
{
    /** Every type, ordered for display. */
    public function ordered(): Collection;

    /** Active types only — what a client form may be saved with. */
    public function activeOptions(): Collection;

    public function findBySlug(string $slug): ?object;

    /** `slug => how many clients carry it`, in one query. */
    public function usageCounts(): array;
}
