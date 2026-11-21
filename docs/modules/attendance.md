# Module: Attendance        Status: ⬜ pending (0/4) — Phase 3
Paths: `app/Modules/Attendance/` · Tables: `attendances`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Attendance.php` | ⬜ |
| check-in (geo + server time) | `Services/AttendanceService.php::checkIn` | ⬜ |
| check-out (geo + server time) | `Services/AttendanceService.php::checkOut` | ⬜ |
| admin daily presence report | `Http/Controllers/Web/AttendanceController.php` | ⬜ |

## Notes
Drives the MR app home screen buttons. One row per MR per date.
Stores `check_in_at/lat/lng`, `check_out_at/lat/lng`. **Server** sets the
timestamp; the device sends only coordinates, so the clock can't be faked.
Re-check-in on the same day updates nothing — guard in the service.

## Next
Migration + service contract, then the two API endpoints.
