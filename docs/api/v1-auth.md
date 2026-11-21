# API v1 — Auth        Status: ✅ done (Phase 1)

Base `/api/v1` · Bearer token (Sanctum) · envelope per `conventions.md`

| Method | Path | Who | Purpose |
|---|---|---|---|
| POST | `/auth/login` | both apps | email + password → token + user |
| POST | `/auth/logout` | both apps | revoke current token |
| GET | `/auth/me` | both apps | profile + role + permissions |

## Login
Body: `email`, `password`, optional `role` (`mr`\|`telecaller`), optional
`device_name` (default `mobile`). Sending `role` makes the server refuse a
user from the other app rather than leaving it to the client.
200 → `data: {token, token_type:"Bearer", user:{…}}`.
401 → `errors.reason` is one of `credentials`, `disabled`, `wrong_surface`
(an admin has no app login), `wrong_app` (right credentials, other app).
Throttled 10/min. Re-login with the same `device_name` replaces that token.

## user object
`id, name, email, mobile, role, role_label, is_active, region_id, regions[],
permissions[]`. `regions` is `[]` until Phase 2 fills it. Fields may be added;
renaming one breaks shipped builds.

## Notes
No registration endpoint — admin creates accounts.
Legacy unversioned `/api/auth/{login,logout,me}` hit the same controller and
envelope while the apps still call them; `/api/auth/register` is gone.
