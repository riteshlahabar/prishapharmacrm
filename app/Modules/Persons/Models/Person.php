<?php

namespace App\Modules\Persons\Models;

use App\Modules\Persons\Enums\PersonType;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A doctor, hospital or medical store — the people MRs visit and telecallers
 * call. Replaced the generic `customers` table in Phase 2.
 *
 * State and relationships only; querying lives in the repository.
 */
#[Fillable([
    'name', 'type', 'email', 'mobile', 'address', 'city', 'specialization',
    'region_id', 'assigned_mr_id', 'assigned_telecaller_id', 'is_active',
])]
class Person extends Model
{
    protected $table = 'persons';

    protected function casts(): array
    {
        return [
            'type' => PersonType::class,
            'is_active' => 'boolean',
        ];
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
        return $this->type?->label() ?? '—';
    }
}
