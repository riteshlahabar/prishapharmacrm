# Module: Auth        Status: ✅ done (5/5) — Phases 1, 6
Paths: `app/Modules/Auth/` · Tables: `users`, `personal_access_tokens`

## Functions
| Function | File | Status |
|---|---|---|
| panel session login (Cuba view) | `Http/Controllers/Web/LoginController.php` | ✅ |
| app login → Sanctum token | `Http/Controllers/Api/AuthController.php` | ✅ |
| re-check account per request | `app/Core/Http/Middleware/EnsureActiveApiUser.php` | ✅ |
| logout (panel + token revoke) | both controllers | ✅ |
| `me` / profile endpoint | `Http/Controllers/Api/AuthController.php` | ✅ |

## Notes
All sign-in rules sit in `AuthService`: credentials, `is_active`, and which
surface a role may use. Admin+telecaller get the panel, MR+telecaller get an
app token; the other combinations are refused. It throws
`AuthenticationFailed`, and each controller maps that to a redirect-back or a
401 envelope — neither knows the other's concern.
Tokens carry ability `role:<value>`; an app may also post `role` to have the
server refuse a user from the other app. Login is throttled (6/min panel,
10/min API). No registration endpoint. Legacy `/api/auth/*` points here too.

## Next
Both apps still call the legacy unversioned routes; delete those once they
move to `/api/v1`. Phase 6 filled `user.regions[]`, empty since Phase 2, and
made a token die with its account (`account_inactive`, `role_changed`).
