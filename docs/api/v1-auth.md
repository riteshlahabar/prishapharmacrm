# API v1 — Auth        Status: ⬜ pending (Phase 1/6)

Base `/api/v1` · Bearer token (Sanctum) · envelope per `conventions.md`

| Method | Path | Who | Purpose |
|---|---|---|---|
| POST | `/auth/login` | both apps | email + password → token + user |
| POST | `/auth/logout` | both apps | revoke current token |
| GET | `/auth/me` | both apps | profile + role + regions |

## Notes
No registration endpoint — admin creates users. Login response carries `role`
so each app can reject a user belonging to the other app.
Legacy unversioned `/api/auth/*` routes stay until apps migrate, then go.
