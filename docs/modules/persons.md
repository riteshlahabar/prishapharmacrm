# Module: Persons       Status: ✅ 6/6 — P2, API P4, policy P8
Paths: `app/Modules/Persons/` · Tables: `persons` (was `customers`)

**The panel calls them clients**; the module, model, table and mobile API
still say person. URLs and route names are `clients.*`; the permissions stay
`persons.*` — those rows exist in every deployment already.

## Functions
| Function | File | Status |
|---|---|---|
| migration (renames `customers`) | `Database/Migrations/` | ✅ |
| admin CRUD at `/clients` | `Http/Controllers/Web/PersonController` | ✅ |
| policy (territory scope) | `Policies/PersonPolicy.php` | ✅ |
| assign MR + telecaller | `Services/PersonService.php` | ✅ |
| filters | `Repositories/EloquentPersonRepository.php` | ✅ |
| API list for both apps | `Http/Controllers/Api/PersonController` | ✅ |

## Notes
Columns: `schema.md`. `type` holds a `client_types.slug`; the kinds are
editable from the panel (`modules/client-types.md`). The `clientType()`
relation gives the label and badge, and list queries eager load it. Whether
a type carries a specialization is that row's flag, and the service nulls
the column for the types without it.

`paginateForUser()` is the scoped entry point: an admin sees everything,
anyone else only their own regions. The clamp lives in the service so no
caller can forget it, and an empty region list shows nothing. Assignee
validation checks the target's **role**, so a telecaller id cannot be posted
into the MR field.

The P2 migration *renames* `customers` and its columns rather than
recreating them, so a deployment keeps its rows; converted rows arrive as
`type=doctor` and need re-typing by hand.

`/api/v1/persons` reuses `paginateForUser` with a role split — a telecaller
gets their regions, an MR their own assignments, active only — and adds
`last_call` via the **Calls contract** (`api/v1-telecaller.md`). `show` and
`edit` go through `findForUser`, which asks `PersonPolicy::view`; denial
reads as 404.

## Next
Done. Writes stay admin work; reads are scoped by territory.
