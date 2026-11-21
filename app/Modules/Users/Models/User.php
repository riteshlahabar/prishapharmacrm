<?php

namespace App\Modules\Users\Models;

use App\Modules\Users\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
