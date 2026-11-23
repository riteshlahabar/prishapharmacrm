<?php

namespace App\Modules\ClientTypes\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\ClientTypes\Repositories\Contracts\ClientTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every `client_types` query in the application lives here.
 */
class EloquentClientTypeRepository extends EloquentRepository implements ClientTypeRepositoryInterface
{
    protected function model(): Model
    {
        return new ClientType;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(fn (Builder $q) => $q->where('label', 'like', $like)->orWhere('slug', 'like', $like));
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        return $query;
    }

    public function ordered(): Collection
    {
        return $this->query()->orderBy('sort_order')->orderBy('label')->get();
    }

    public function activeOptions(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->query()->where('slug', $slug)->first();
    }

    /**
     * One grouped query rather than a count per row. Reads `persons` directly
     * — the only place in this module that does — because a type's whole job
     * is to describe that column, and asking the Persons service for a count
     * per slug would be the same query behind a slower loop.
     *
     * @return array<string, int>
     */
    public function usageCounts(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('persons')) {
            return [];
        }

        return DB::table('persons')
            ->groupBy('type')
            ->selectRaw('type, count(*) as total')
            ->pluck('total', 'type')
            // Not `intval(...)`: map hands the callback the key as well, and
            // a slug key would arrive as intval()'s $base.
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
