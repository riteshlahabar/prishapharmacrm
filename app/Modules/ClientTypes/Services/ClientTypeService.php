<?php

namespace App\Modules\ClientTypes\Services;

use App\Modules\ClientTypes\Exceptions\ClientTypeInUse;
use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\ClientTypes\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClientTypeService implements ClientTypeServiceInterface
{
    /**
     * Resolved rows for this request. A client list renders the label and the
     * badge of every row it shows, and the table is small and read almost
     * never written, so it is read once instead of once per client.
     *
     * @var Collection<int, ClientType>|null
     */
    private ?Collection $cache = null;

    public function __construct(private readonly ClientTypeRepositoryInterface $types) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->types->paginate($perPage, $filters);
    }

    public function find(int|string $id): ClientType
    {
        /** @var ClientType */
        return $this->types->findOrFail($id);
    }

    public function create(array $data): ClientType
    {
        $this->cache = null;

        /** @var ClientType */
        return $this->types->create($this->attributes($data, creating: true));
    }

    /** The slug is not in the update payload: clients store it. */
    public function update(int|string $id, array $data): ClientType
    {
        $this->cache = null;

        /** @var ClientType */
        return $this->types->update($id, $this->attributes($data, creating: false));
    }

    /**
     * Every type is equal here: the master is the admin's to shape, so the
     * three the migration ships with are no more protected than one added
     * yesterday. Being *used* is the only thing that blocks a delete — that
     * guard is about not orphaning `persons.type`, not about which row it is.
     */
    public function delete(int|string $id): bool
    {
        $type = $this->find($id);

        $used = $this->usageCounts()[$type->slug] ?? 0;

        if ($used > 0) {
            throw ClientTypeInUse::make($type->label, $used);
        }

        $this->cache = null;

        return $this->types->delete($id);
    }

    public function toggleActive(int|string $id): bool
    {
        $type = $this->find($id);
        $type->is_active = ! $type->is_active;
        $type->save();
        $this->cache = null;

        return $type->is_active;
    }

    public function activeOptions(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }

    public function optionsFor(?string $currentSlug): Collection
    {
        return $this->all()
            ->filter(fn (ClientType $type) => $type->is_active || $type->slug === $currentSlug)
            ->values();
    }

    public function all(): Collection
    {
        return $this->cache ??= $this->types->ordered();
    }

    public function findBySlug(string $slug): ?ClientType
    {
        /** @var ClientType|null */
        return $this->all()->firstWhere('slug', $slug);
    }

    public function usageCounts(): array
    {
        return $this->types->usageCounts();
    }

    /**
     * @param  bool  $creating  the slug is written once; an edit may not move
     *                          it, because every client row stores it
     */
    private function attributes(array $data, bool $creating): array
    {
        $attributes = [
            'label' => trim((string) $data['label']),
            'colour' => in_array($data['colour'] ?? '', ClientType::COLOURS, true) ? $data['colour'] : 'primary',
            'has_specialization' => (bool) ($data['has_specialization'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if ($creating) {
            $attributes['slug'] = Str::of($data['slug'] ?? $data['label'])->slug()->limit(40, '')->value();
        }

        return $attributes;
    }
}
