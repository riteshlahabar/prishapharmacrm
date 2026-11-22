# Module: Persons        Status: ✅ 6/6 — Phase 2, API 4, policy 6/8
Paths: `app/Modules/Persons/` · Tables: `persons` (was `customers`)

## Functions
| Function | File | Status |
|---|---|---|
| migration + `PersonType` enum | `Database/Migrations/`, `Enums/PersonType.php` | ✅ |
| admin CRUD | `Http/Controllers/Web/PersonController.php` | ✅ |
| policy (territory scope) | `Policies/PersonPolicy.php` | ✅ |
| assign MR + telecaller | `Services/PersonService.php` | ✅ |
| filter by type/region/assignee | `Repositories/EloquentPersonRepository` | ✅ |
| API list for both apps | `Http/Controllers/Api/PersonController.php` | ✅ |

## Notes
Fields: name, type, mobile, email, address, city, region_id, specialization,
assigned_mr_id, assigned_telecaller_id, is_active.

`paginateForUser()` is the scoped entry point: an admin sees everything, anyone
else only their own regions. The clamp lives in the service so no caller can
forget it, and an empty region list shows nothing, not everything.

Specialization belongs to doctors; the service nulls it for other types so a
type change leaves nothing stale. Assignee validation checks the target's
**role**, so a telecaller id cannot be posted into the MR field.

The Phase 2 migration *renames* `customers` and its columns rather than
recreating them, so a deployment keeps its rows. Converted rows land as
`type=doctor` and need re-typing by hand.

`/api/v1/persons` landed with Phase 4 for the telecaller call queue. It reuses
`paginateForUser` plus a role split: a telecaller gets their regions, an MR
only their own assignments. Active rows only. The telecaller view carries
`last_call` per row via the **Calls service contract**, so this module never
touches `call_logs`.

Phase 8 added `findForUser`: `show`/`edit` were reached by id and never asked
`PersonPolicy::view`, so the clamp was cosmetic — any telecaller could read
any contact. A denial reads as 404.

## Next
Done. Writes stay admin work: reads are scoped by territory,
`update`/`delete` only by permission.
