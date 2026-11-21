# Module: Calls        Status: ⬜ pending (0/4) — Phase 4
Paths: `app/Modules/Calls/` · Tables: `call_logs`

## Functions
| Function | File | Status |
|---|---|---|
| migration + `CallStatus` enum | `Database/Migrations/`, `Enums/CallStatus.php` | ⬜ |
| log a call (status + notes) | `Services/CallLogService.php` | ⬜ |
| region-scoped person queue | `Repositories/CallLogRepository.php` | ⬜ |
| follow-up list | `Http/Controllers/Api/CallLogController.php` | ⬜ |

## Notes
Telecaller calls a person, then records **status + notes**. Status enum:
interested, not_interested, callback, not_reachable, converted.
Person queue is filtered to the telecaller's assigned regions.
Telecaller app's existing `call_logs`/`call_schedules` modules map here.

## Next
Enum + migration, then the queue query.
