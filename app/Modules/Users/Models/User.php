<?php

namespace App\Modules\Users\Models;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Database\Factories\UserFactory;
use App\Modules\Users\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Every account in the system: admin, MR and telecaller alike. The model holds
 * state and relationships only — all querying lives in
 * App\Modules\Users\Repositories (see docs/conventions.md).
 *
 * The auth provider points here via config/auth.php.
 */
#[Fillable(['name', 'email', 'mobile', 'role', 'region_id', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /** An MR's single territory. Null for telecallers and admins. */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /** A telecaller's territories. Empty for MRs, who use region() instead. */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'telecaller_region', 'user_id', 'region_id')
            ->withTimestamps();
    }

    /**
     * The regions this user covers, flattened to one collection whichever
     * shape their role uses — so a list template does not branch on role.
     * Reads the loaded relations, so eager-load `region` and `regions` to
     * keep a list from turning into N+1.
     *
     * @return Collection<int, Region>
     */
    public function getCoveredRegionsAttribute(): Collection
    {
        return match ($this->role) {
            UserRole::Mr => $this->region ? new Collection([$this->region]) : new Collection,
            UserRole::Telecaller => $this->regions,
            default => new Collection,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMr(): bool
    {
        return $this->role === UserRole::Mr;
    }

    public function isTelecaller(): bool
    {
        return $this->role === UserRole::Telecaller;
    }

    /** A disabled user may neither sign into the panel nor get an app token. */
    public function canSignIn(): bool
    {
        return $this->is_active === true && $this->role !== null;
    }

    public function roleLabel(): string
    {
        return $this->role?->label() ?? '—';
    }
}
