# Module: Attendance        Status: ✅ done (5/5) — Phases 3, 6
Paths: `app/Modules/Attendance/` · Tables: `attendances`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Attendance.php` | ✅ |
| check-in (geo + server time) | `Services/AttendanceService.php::checkIn` | ✅ |
| policy (own rows; no edits) | `Policies/AttendancePolicy.php` | ✅ |
| region scope + daily trend | `Repositories/EloquentAttendanceRepository.php` | ✅ |
| check-out (geo + server time) | `Services/AttendanceService.php::checkOut` | ✅ |
| admin daily presence report | `Http/Controllers/Web/AttendanceController.php` | ✅ |
| app endpoints | `Http/Controllers/Api/AttendanceController.php` | ✅ |

## Notes
Drives the MR app home screen buttons. One row per MR per date, with a unique
`(mr_id, date)` index behind the service guard. Stores `check_in_at/lat/lng`
and `check_out_at/lat/lng`. The **server** sets every timestamp; the device
sends only coordinates, so the clock cannot be faked.

Sequence is enforced in the service and answered as **409**, not 422: check in
once, check out once, in that order. A racing second request loses on the
unique index and gets the same conflict. `GET today` returns `can_check_in` /
`can_check_out` so a reinstall cannot guess wrong.

The panel report is read-only (no create or edit) and defaults to today.
Phase 8: it now goes through `paginateForUser` / `summaryForUser`, so a
telecaller granted `attendance.view` sees only reps in the regions they
cover — it was the one panel list still reading company-wide.

## Next
Done. The dashboard charts these rows. Nobody edits a punch — the policy has
no `update`, since the times are the record's evidence.
