# Module: Messages        Status: ⬜ pending (0/5) — Phase 5
Paths: `app/Modules/Messages/` · Tables: `messages`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Message.php` | ⬜ |
| telecaller sends to MR | `Services/MessageService.php::send` | ⬜ |
| MR inbox list | `Http/Controllers/Api/MessageController.php::index` | ⬜ |
| unread count (polling) | `…::unreadCount` | ⬜ |
| mark read | `…::markRead` | ⬜ |

## Notes
**No Firebase.** In-app only: MR app polls `unread-count` on resume and on a
timer, then loads the inbox. Message may reference a person and/or a call log
so the MR sees the context. Fields: from_user_id, to_user_id, person_id?,
call_log_id?, subject, body, is_read, read_at.

## Next
Migration + send service, then the three endpoints.
