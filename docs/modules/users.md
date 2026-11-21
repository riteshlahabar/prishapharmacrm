# Module: Users        Status: ⬜ pending (0/5) — Phase 1
Paths: `app/Modules/Users/` · Tables: `users`

## Functions
| Function | File | Status |
|---|---|---|
| migration: add `mobile`, `role`, `region_id`, `is_active` | `Database/Migrations/` | ⬜ |
| admin CRUD for MR | `Http/Controllers/Web/MrController.php` | ⬜ |
| admin CRUD for telecaller | `Http/Controllers/Web/TelecallerController.php` | ⬜ |
| password set/reset by admin | `Services/UserService.php` | ⬜ |
| activate / deactivate | `Services/UserService.php` | ⬜ |

## Notes
Admin creates users with **name, mobile, email, password**. Users never
self-register. Telecallers carry one or more regions (see `regions.md`).

## Next
Write the users migration, then the MR CRUD on the Cuba datatable layout.
