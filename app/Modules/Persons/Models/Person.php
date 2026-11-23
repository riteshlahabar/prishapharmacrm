<?php

namespace App\Modules\Persons\Models;

use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Persons\Database\Factories\PersonFactory;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client: a doctor, a hospital, a medical store, or any other type an admin
 * has added — the people MRs visit and telecallers call. Replaced the generic
 * `customers` table in Phase 2; the panel calls them clients, the model and
 * the mobile API still say person (docs/modules/persons.md).
 *
 * `type` holds a `client_types.slug`, not an enum case: the kinds of client
 * are editable from the panel. It stays a plain string because both Flutter
 * apps decode it as one.
 *
 * State and relationships only; querying lives in the repository.
 */
#[Fillable([
    'name', 'type', 'email', 'mobile', 'address', 'city', 'specialization',
    'region_id', 'assigned_mr_id', 'assigned_telecaller_id', 'is_active',
])]
#[UseFactory(PersonFactory::class)]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $table = 'persons';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * The type master row behind `type`. Joined on the slug rather than an
     * id so the column keeps the value the API publishes; list queries eager
     * load it (EloquentPersonRepository) so a page of clients reads it once.
     */
    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'type', 'slug');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function assignedMr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_mr_id');
    }

    public function assignedTelecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_telecaller_id');
    }

    public function typeLabel(): string
    {
        return $this->clientType?->label ?? ($this->type ? ucfirst((string) $this->type) : '—');
    }

    /** Cuba badge class, so every list colour-codes the type the same way. */
    public function typeBadgeClass(): string
    {
        return $this->clientType?->badgeClass() ?? 'badge-light-secondary';
    }

    /** Only some types carry one — a doctor does, a medical store does not. */
    public function hasSpecialization(): bool
    {
        return (bool) $this->clientType?->has_specialization;
    }
}
