# Module: Persons        Status: ✅ 6/6 — Phase 2, API 4, policy 6
Paths: `app/Modules/Persons/` · Tables: `persons` (was `customers`)

## Functions
| Function | File | Status |
|---|---|---|
| migration + `PersonType` enum | `Database/Migrations/`, `Enums/PersonType.php` | ✅ |
| admin CRUD | `Http/Controllers/Web/PersonController.php` | ✅ |
| policy (territory scope) | `Policies/PersonPolicy.php` | ✅ |
| assign MR + telecaller | `Services/PersonService.php` | ✅ |
| filter by type / region / assignee | `Repositories/EloquentPersonRepository::applyFilters` | ✅ |
| API list for both apps | `Http/Controllers/Api/PersonController.php` | ✅ |

## Notes
Fields: name, type, mobile, email, address, city, region_id, specialization,
assigned_mr_id, assigned_telecaller_id, is_active.

`paginateForUser()` is the scoped entry point: an admin sees everything, anyone
else only their own regions. The clamp lives in the service so no caller can
forget it, and an empty region list shows nothing rather than everything.

Specialization belongs to doctors; the service nulls it for other types so a
type change leaves nothing stale. Assignee validation checks the target's
**role**, so a telecaller id cannot be posted into the MR field.

The Phase 2 migration *renames* `customers` and its columns rather than
recreating them, so a live deployment keeps its rows. Converted rows land as
`type=doctor` and need re-typing by hand.

`/api/v1/persons` landed with Phase 4, since the telecaller app needs a call
queue. It reuses `paginateForUser` and adds the role split: a telecaller gets
their regions, an MR only the persons assigned to them. Active rows only. The
telecaller view carries `last_call` per row, fetched through the **Calls
service contract** so this module never touches `call_logs`.

## Next
Policy and API polish landed in Phase 6. Phase 7 reads the list through
the service contract for the dashboards.
