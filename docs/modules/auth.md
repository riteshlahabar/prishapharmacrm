# Module: Auth        Status: ✅ done (4/4) — Phase 1
Paths: `app/Modules/Auth/` · Tables: `users`, `personal_access_tokens`

## Functions
| Function | File | Status |
|---|---|---|
| panel session login (Cuba view) | `Http/Controllers/Web/LoginController.php` | ✅ |
| app login → Sanctum token | `Http/Controllers/Api/AuthController.php` | ✅ |
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
Phase 6: move both apps onto `/api/v1`, then delete the legacy routes.
