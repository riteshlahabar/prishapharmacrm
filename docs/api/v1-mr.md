# API v1 — MR app        Status: ✅ done (Phases 3–5)

Base `/api/v1` · Bearer token · `ability:role:mr`, so a telecaller token is
**403** here even though it authenticates. Envelope, error reasons and
pagination: **`v1-common.md`**.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/attendance/today` | ✅ | button state for the home screen |
| POST | `/attendance/check-in` | ✅ | lat/lng → server timestamps the row |
| POST | `/attendance/check-out` | ✅ | lat/lng → closes today's row |
| GET | `/attendance/history` | ✅ | own rows, paged (`date_from/to`) |
| POST | `/visits` | ✅ | person_id, purpose, in/out time, lat/lng |
| GET | `/visits` | ✅ | own visit history, paginated |
| GET | `/persons` | ✅ | persons assigned to this MR (active only) |
| GET | `/messages` | ✅ | inbox (`?unread=1`), newest first |
| GET | `/messages/unread-count` | ✅ | polled — no Firebase |
| POST | `/messages/{id}/read` | ✅ | mark one read |
| POST | `/messages/read-all` | ✅ | mark the inbox read |

## Attendance
The device sends coordinates only; the server sets every time. `today` always
answers, even on a day with no row, so the buttons never guess:
`can_check_in`, `can_check_out`, `status`, `worked_minutes`.
**409** with `errors.reason` = `already_checked_in`, `not_checked_in` or
`already_checked_out` when the day's sequence forbids the call.

## Visits
`mr_id` comes from the token, never the body. `purpose` is a `VisitPurpose`
value. `out_time` ≥ `in_time`, `visit_date` not in the future, lat/lng
together → else **422**. `location_fetched_at` (device GPS read) sits beside
`recorded_at` (server receipt), so a late entry shows as one.

## Lists
History, visits, persons and the inbox page alike, scoped to the token's MR.

## Messages
Read-only here: an MR receives, a telecaller sends. `unread-count` is one
indexed COUNT, cheap on a timer. Marking is idempotent and recipient-only —
anyone else's message is **404**. `is_read` derives from `read_at`, the only
state stored.
