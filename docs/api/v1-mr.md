# API v1 — MR app        Status: 🟡 attendance + visits done (Phase 3)

Base `/api/v1` · Bearer token · `ability:role:mr`, so a telecaller token is
**403** here even though it authenticates. Envelope per `conventions.md`.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/attendance/today` | ✅ | button state for the home screen |
| POST | `/attendance/check-in` | ✅ | lat/lng → server timestamps the row |
| POST | `/attendance/check-out` | ✅ | lat/lng → closes today's row |
| GET | `/attendance/history` | ✅ | own rows, paginated (`date_from/to`) |
| POST | `/visits` | ✅ | person_id, purpose, in/out time, lat/lng |
| GET | `/visits` | ✅ | own visit history, paginated |
| GET | `/persons` | ⬜ P6 | persons assigned to this MR |
| GET | `/messages` | ⬜ P5 | inbox from telecallers |
| GET | `/messages/unread-count` | ⬜ P5 | polled — no Firebase |
| POST | `/messages/{id}/read` | ⬜ P5 | mark read |

## Attendance
The device sends coordinates only; the server sets every time. `today` always
answers, even on a day with no row, so the two buttons never guess:
`can_check_in`, `can_check_out`, `status`, `worked_minutes`.
**409** with `errors.reason` = `already_checked_in`, `not_checked_in` or
`already_checked_out` when the day's sequence forbids the call.

## Visits
`mr_id` is taken from the token and rejected if sent. `purpose` is one of the
`VisitPurpose` values. `out_time` must be ≥ `in_time`, `visit_date` cannot be
in the future, and lat/lng must arrive together → otherwise **422**.
`location_fetched_at` (when the device read the GPS) is stored next to
`recorded_at` (when the server received it), so a late entry is visible.
