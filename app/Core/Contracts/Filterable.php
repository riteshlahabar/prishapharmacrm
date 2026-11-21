<?php

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Implemented by repositories that translate request filters into query
 * constraints, so controllers never build queries themselves (SRP).
 */
interface Filterable
{
    public function applyFilters(Builder $query, array $filters): Builder;
}
