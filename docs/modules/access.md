# Module: Access        Status: ✅ done (4/4) — Phase 1
Paths: `app/Modules/Access/` · Package: `spatie/laravel-permission`
Tables: `roles`, `permissions`, `model_has_*`, `role_has_permissions`

## Functions
| Function | File | Status |
|---|---|---|
| seed `admin`/`mr`/`telecaller` roles | `Database/Seeders/RoleSeeder.php` | ✅ |
| seed module permissions | `Database/Seeders/PermissionSeeder.php` | ✅ |
| admin UI: grant permissions to a telecaller | `Http/Controllers/Web/RolePermissionController.php` | ✅ |
| route + sidebar capability checks | `permission:` middleware, `@can` | ✅ |

## Notes
`Support/PermissionCatalog.php` is the whole vocabulary (30 names,
`<group>.<ability>`); seeders, the grant UI and the sidebar all read it.
Adding a capability = edit that file + re-seed.
Roles: three, named by `UserRole`. `admin` is re-synced to the full catalog on
every seed; `telecaller` gets a baseline once and is then left alone; `mr`
gets none (app only).
A telecaller's extras are **direct** permissions on the user, on top of the
role baseline. `grantable()` omits `access`, `mrs`, `telecallers`, and
`syncDirect` filters against it, so the form cannot escalate.

## Next
Phase 4: scope the telecaller's data by region, not only by capability.
