# API v1 — common contract        Status: ✅ done (Phase 6)

What every `/api/v1` endpoint shares. Read before the per-app pages.

## Envelope
One shape, success or failure — `ApiResponse`, and `ApiExceptionRenderer` for
anything thrown. Branch on `success`, not on the HTTP code alone.

```
{ "success": true,  "message": "Visits loaded.", "data": …, "meta": {…} }
{ "success": false, "message": "Not found.", "errors": {"reason":"not_found"} }
```
`meta` on paginated lists only, `errors` on failures only.

## errors.reason
Stable token to switch on — `message` is display text and may be reworded.

| HTTP | reason | Meaning |
|---|---|---|
| 401 | `unauthenticated` | no/expired token → sign in |
| 401 | login reasons | see `v1-auth.md` |
| 403 | `forbidden` | this app's token may not call this endpoint |
| 403 | `account_inactive` | account switched off → sign out, clear token |
| 403 | `role_changed` | role moved since login → sign out, re-login |
| 404 | `not_found` | no such row, **or** not yours (below) |
| 405 | `method_not_allowed` | wrong verb |
| 409 | `already_checked_in` … | sequence conflict — `v1-mr.md` |
| 422 | `validation` | field errors sit beside `reason` |
| 429 | `throttled` | honour `Retry-After` |
| 500 | `server_error` | retry; detail is logged, not returned |

## 403 vs 404
**403** is the account: your role or app may not use this feature. **404** is
the row: absent, *or* outside your scope — the same answer on purpose, since
a 403 would confirm it exists. The rules live in each module's policy, which
renders as 403 in the panel. Phase 7 moves the panel onto them.

## Pagination
Every list is scoped server-side and paginated alike. `?page=`, `?per_page=`
(default **20**, max **100**). An unusable `per_page` — `0`, negative,
non-numeric — falls back to the default, and `meta.per_page` reports the size
actually used.

`meta: {current_page, last_page, per_page, total, from, to, has_more}`.
`/mrs` is the one exception: a small picker, plain array, no `meta`.
