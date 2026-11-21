# Conventions

## Modular monolith
- One module per business capability: `app/Modules/<Name>/`.
- Module owns `Http/{Controllers/Web,Controllers/Api,Requests,Resources}`,
  `Services`, `Repositories`, `Models`, `Policies`, `Routes`,
  `Database/Migrations`, `Resources/Views`.
- Register a module by adding its provider to `config/modules.php`. Nothing
  else in the app changes.
- Cross-module access goes through the other module's **service contract**,
  never its repository or model directly.

## SOLID rules
- **SRP** Controller validates + delegates. Service holds business rules.
  Repository holds persistence. No queries in controllers or views.
- **OCP** Extend `App\Core\Repositories\EloquentRepository`; override
  `applyFilters()` instead of editing the base class.
- **LSP/ISP** Small contracts in `Services/Contracts` and
  `Repositories/Contracts`; a consumer depends only on what it calls.
- **DIP** Type-hint contracts. Bind concretes in the module provider's
  `bindings()`.

## API
- All mobile endpoints under `/api/v1`, auth via Sanctum bearer token.
- Every response built by `App\Core\Http\ApiResponse`
  (`success` / `error` / `paginated`). Never hand-roll a JSON shape.
- Envelope: `{success, message, data}`; errors add `errors{}`; lists add `meta{}`.

## Panel (Blade + Cuba)
- Extend `layouts.master`. Assets live in `public/assets` (Cuba, committed).
- Every web route sits behind `auth`; capability checks use spatie permissions.

## Naming
- Tables plural snake_case; models singular. Enums as PHP enums in the module.
- Times stored UTC; geo as `decimal(10,7)` lat / `decimal(10,7)` lng.

## Docs discipline
- Each `docs/**` file ≤ 2 KB, `CLAUDE.md` ≤ 100 lines; enforced by
  `docs/check-docs.sh` (run before committing).
- A commit touching a module updates that module's status table + one line in
  the `CLAUDE.md` session log.
