# Module: Auth        Status: ⬜ pending (0/4) — Phase 1
Paths: `app/Modules/Auth/` · Tables: `users`, `personal_access_tokens` (✅ migrated)

## Functions
| Function | File | Status |
|---|---|---|
| panel session login (Cuba view) | `Http/Controllers/Web/LoginController.php` | ⬜ |
| app login → Sanctum token | `Http/Controllers/Api/AuthController.php` | ⬜ |
| logout (panel + token revoke) | both controllers | ⬜ |
| `me` / profile endpoint | `Http/Controllers/Api/AuthController.php` | ⬜ |

## Notes
Apps require **login only** — no registration. Legacy `App\Http\Controllers\
Api\AuthController` has a `register` route to be removed in Phase 1.
Token abilities carry the role so each app can refuse a wrong-role login.

## Next
Move legacy auth controllers into this module; delete `register`.
