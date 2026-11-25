# Module: ClientTypes    Status: ✅ — what a client may be
Paths: `app/Modules/ClientTypes/` · Tables: `client_types`

## Functions
| Function | File | Status |
|---|---|---|
| table + three starter rows | `Database/Migrations/` | ✅ |
| admin CRUD, no `show` | `Http/Controllers/Web/ClientTypeController` | ✅ |
| slug fixed, in-use guard | `Services/ClientTypeService.php` | ✅ |
| options + usage counts | `Repositories/EloquentClientTypeRepository` | ✅ |

## Notes
Doctor / hospital / medical store were a PHP enum, so adding "chemist" meant
a deploy. They are rows here, editable at `/client-types` under the Clients
menu, behind `client_types.*` (admin only — `grantable()` drops them).

`persons.type` still stores the **slug**, with no foreign key: that column is
part of the mobile API contract both Flutter apps decode, and an id would
change the wire format for nothing. Two consequences the panel enforces:

- the slug is written once and shown read-only on edit — moving it would
  orphan every client carrying it;
- a type **in use** cannot be deleted — the only rule. No slug is named
  anywhere in the code, so doctor/hospital/medical delete like any other.
  Deactivating retires one that is in use: its clients keep it, the client
  form drops it, and `optionsFor()` still offers it when editing a client
  that has it.

`has_specialization` replaces the hard-coded "doctors only" rule: the client
form reads the flag off the selected option, and `PersonService` clears the
column server-side for any type without it.

The service is a **singleton** memoising the table per request: a client list
renders a label and badge per row off a tiny, rarely written table.

The three starter rows come from the migration, not a seeder — `migrate`
alone must leave the master usable. That migration also adopts any unknown
`persons.type` it finds, so a converted `customers` table stays resolvable.

## Next
Done. A wider colour picker than the six Cuba badges is the only obvious
extension, and nobody has asked.
