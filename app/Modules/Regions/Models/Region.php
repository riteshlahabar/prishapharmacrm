<?php

namespace App\Modules\Regions\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sales territory. Holds state and relationships only — querying lives in
 * App\Modules\Regions\Repositories (docs/conventions.md).
 */
#[Fillable(['name', 'code', 'is_active'])]
class Region extends Model
{
    protected $table = 'regions';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** MRs whose single territory this is. */
    public function medicalReps(): HasMany
    {
        return $this->hasMany(User::class, 'region_id');
    }

    /** Telecallers who cover this region among others. */
    public function telecallers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'telecaller_region', 'region_id', 'user_id')
            ->withTimestamps();
    }

    public function label(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
