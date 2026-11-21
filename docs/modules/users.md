# Module: Users        Status: ✅ done (5/5) — Phase 1
Paths: `app/Modules/Users/` · Tables: `users`

## Functions
| Function | File | Status |
|---|---|---|
| migration: `mobile`, `role`, `region_id`, `is_active` | `Database/Migrations/2026_11_21_190000_*` | ✅ |
| admin CRUD for MR | `Http/Controllers/Web/MrController.php` | ✅ |
| admin CRUD for telecaller | `Http/Controllers/Web/TelecallerController.php` | ✅ |
| password set/reset by admin | `Services/UserService.php` | ✅ |
| activate / deactivate | `Services/UserService.php` | ✅ |
| create / reset the panel admin | `Console/SetAdminCommand.php` | ✅ |

## Notes
`App\Modules\Users\Models\User` is the auth model (`config/auth.php`).
`UserRole` enum = the `users.role` value **and** the spatie role name;
`UserService` writes both together, nothing else writes a role.
Both CRUDs are one `UserCrudController`; a subclass declares role, route name
and wording, and the views are shared. Routes name the parameter `user`.
`findOfRoleOrFail` 404s on a cross-role id, so `/mrs/{telecallerId}` misses.
Disabling an account deletes its Sanctum tokens.
`region_id` is written but not pickable until `regions` exists [P2].

`php artisan users:set-admin <email>` creates an admin or resets one's
password (prompts when `--password` is omitted) and drops its app tokens.
`AdminUserSeeder` never overwrites an existing password, so this is the only
way to change one.

## Next
Phase 2: region picker on the form, telecaller↔region pivot.
