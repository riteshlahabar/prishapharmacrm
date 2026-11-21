# Module: Persons        Status: ⬜ pending (0/5) — Phase 2
Paths: `app/Modules/Persons/` · Tables: `persons` (replaces `customers`)

## Functions
| Function | File | Status |
|---|---|---|
| migration + `PersonType` enum (doctor/hospital/medical) | `Database/Migrations/`, `Enums/PersonType.php` | ⬜ |
| admin CRUD | `Http/Controllers/Web/PersonController.php` | ⬜ |
| assign MR + telecaller | `Services/PersonService.php` | ⬜ |
| filter by type / region / assignee | `Repositories/PersonRepository.php::applyFilters` | ⬜ |
| API list for both apps | `Http/Controllers/Api/PersonController.php` | ⬜ |

## Notes
Replaces the legacy `customers` table/module (migrated + renamed in Phase 2).
Fields: name, type, mobile, email, address, city, region_id, specialization,
assigned_mr_id, assigned_telecaller_id, is_active.

## Next
Write the migration that converts `customers` → `persons` without data loss.
