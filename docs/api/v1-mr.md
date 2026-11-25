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
| GET | `/visits` | own visit history, paginated (`?date_from`, `?date_to`) |
| PATCH | `/visits/{id}` | correct own entry — purpose, times, notes, products |
| GET | `/visits/{id}/photo` | the shopfront photo itself, token-scoped |
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
the reply report `has_photo` plus a `photo_url` the app fetches **with its
bearer token** — the file stays on a private disk and that route carries the
visit's own scope. Null when there is no photo. Contract in
**`../visit-photos.md`**.

`PATCH /visits/{id}` corrects an entry: `purpose`, `in_time`/`out_time`
(both or neither), `notes`, `products`. Keys left out are left alone; an
empty `products` array clears the lines. `person_id`, the coordinates and
the photo are **not** amendable — the first would rewrite history, the
other two are the evidence that the rep stood there. Author-only
(`VisitPolicy::amend`, no admin bypass): anyone else's visit is a **404**.

## Lists
History, visits, persons and the inbox page alike, scoped to the token's MR.

## Messages
Read-only here: an MR receives, a telecaller sends. `unread-count` is one
indexed COUNT, cheap on a timer. Marking is idempotent and recipient-only —
anyone else's is **404**; `is_read` derives from `read_at`.
