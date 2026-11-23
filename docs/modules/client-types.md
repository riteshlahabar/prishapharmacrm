# Module: ClientTypes    Status: ✅ — what a client may be
Paths: `app/Modules/ClientTypes/` · Tables: `client_types`

## Functions
| Function | File | Status |
|---|---|---|
| table + the three defaults | `Database/Migrations/` | ✅ |
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
- a type in use cannot be deleted, and neither can the three built-ins.
  Deactivating is the way to retire one: existing clients keep it, the client
  form stops offering it, and `optionsFor()` still shows it when editing a
  client that has it.

`has_specialization` replaces the hard-coded "doctors only" rule: the client
form reads the flag off the selected option, and `PersonService` clears the
column server-side for any type without it.

The service is a **singleton** and memoises the table for the request: a page
of clients renders a label and a badge per row off a tiny, rarely written
table.

The defaults are inserted by the migration, not a seeder — `migrate` alone on
an existing deployment must leave the type master usable. The same migration
adopts any unknown `persons.type` value it finds, so a converted `customers`
table keeps every client resolvable.

## Next
Done. A colour picker wider than the six Cuba badge names is the only
obvious extension, and nobody has asked.
