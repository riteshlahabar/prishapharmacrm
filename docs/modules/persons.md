# Module: Persons       Status: ✅ 6/6 — P2, API P4, policy P8
Paths: `app/Modules/Persons/` · Tables: `persons` (was `customers`)

**The panel calls them clients**; the module, model, table and mobile API
still say person. URLs and route names are `clients.*`; the permissions stay
`persons.*`, which every deployment already has.

## Functions
| Function | File | Status |
|---|---|---|
| migration (renames `customers`) | `Database/Migrations/` | ✅ |
| admin CRUD at `/clients` | `Http/Controllers/Web/PersonController` | ✅ |
| policy (territory) | `Policies/PersonPolicy.php` | ✅ |
| assign MR + telecaller | `Services/PersonService.php` | ✅ |
| filters | `Repositories/EloquentPersonRepository.php` | ✅ |
| API list, both apps | `Http/Controllers/Api/PersonController` | ✅ |
| app add + edit | `Services/PersonService.php` | ✅ |

## Notes
Columns: `schema.md`. `type` holds a `client_types.slug`, a master the
panel edits (`modules/client-types.md`). The `clientType()`
relation gives the label and badge, and list queries eager load it. Its
`has_specialization` flag decides whether the column is kept at all.

`paginateForUser()` is the scoped entry point: an admin sees everything,
anyone else only their own regions. The clamp lives in the service, and an
empty region list shows nothing. Assignee validation checks the target's
**role**.

The P2 migration *renames* `customers` and its columns, so a deployment
keeps its rows; converted rows arrive as `type=doctor` and need re-typing.

`/api/v1/persons` reuses `paginateForUser` with a role split — a telecaller
gets their regions, an MR their own assignments, active only — and adds
`last_call` via the **Calls contract** (`api/v1-telecaller.md`). `show` and
`edit` go through `findForUser` (`PersonPolicy::view`); denial reads as 404.

`createForApp` and `updateForApp` are the apps' only writes; both clamp on
**ownership** — anyone else's client is a 404 (`api/v1-clients.md`).

## Next
Done. Apps add and correct their own clients; the rest is panel work.
