# API v1 — MR app        Status: ✅ done (Phases 3–5, 15)

Base `/api/v1` · Bearer token · `ability:role:mr`, so a telecaller token is
**403** here even though it authenticates. Envelope: **`v1-common.md`**.

| Method | Path | Purpose |
|---|---|---|
| GET | `/attendance/today` | button state for the home screen |
| POST | `/attendance/check-in` | lat/lng → server stamps the row |
| POST | `/attendance/check-out` | lat/lng → closes today's row |
| GET | `/attendance/history` | own rows, paged (`date_from/to`) |
| POST | `/visits` | person, purpose, times, lat/lng, products, photo |
| GET | `/visits` | own visit history, paginated |
| GET | `/persons` | persons assigned to this MR (active only) |
| GET | `/products` | the catalogue — **`v1-products.md`** |
| GET | `/messages` | inbox (`?unread=1`), newest first |
| GET | `/messages/unread-count` | polled — no Firebase |
| POST | `/messages/{id}/read` · `/read-all` | mark one, or the inbox |

## Attendance
The device sends coordinates only; the server sets every time. `today`
answers even on a day with no row, so the buttons never guess:
`can_check_in`, `can_check_out`, `status`, `worked_minutes`. **409** with
`errors.reason` = `already_checked_in`, `not_checked_in` or
`already_checked_out`.

## Visits
`mr_id` comes from the token, never the body. `purpose` is a `VisitPurpose`.
`out_time` ≥ `in_time`, `visit_date` not future, lat/lng together → **422**.
`location_fetched_at` (device GPS) sits beside `recorded_at` (server
receipt), so a late entry shows as one.

A visit may carry one shopfront photo, which makes the POST multipart and
the reply report `has_photo` — contract in **`../visit-photos.md`**.

## Lists
History, visits, persons and the inbox page alike, scoped to the token's MR.

## Messages
Read-only here: an MR receives, a telecaller sends. `unread-count` is one
indexed COUNT, cheap on a timer. Marking is idempotent and recipient-only —
anyone else's is **404**; `is_read` derives from `read_at`.
