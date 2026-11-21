# Module: Access        Status: ⬜ pending (0/4) — Phase 1
Paths: `app/Modules/Access/` · Package: `spatie/laravel-permission`
Tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (migrated ✅)

## Functions
| Function | File | Status |
|---|---|---|
| seed `admin` + `telecaller` roles | `Database/Seeders/RoleSeeder.php` | ⬜ |
| seed module permissions | `Database/Seeders/PermissionSeeder.php` | ⬜ |
| admin UI: grant permissions to a telecaller | `Http/Controllers/Web/RolePermissionController.php` | ⬜ |
| sidebar/route capability checks | `Policies/` + middleware | ⬜ |

## Notes
Role & permission UI is for the **telecaller** role only (admin has all).
Telecaller logs into the panel and sees only what admin granted.

## Next
Define the permission list (per module: view/create/update/delete) and seed it.
