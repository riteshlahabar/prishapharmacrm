# Module: Messages        Status: ✅ done (6/6) — Phases 5, 6
Paths: `app/Modules/Messages/` · Tables: `messages`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Message.php` | ✅ |
| telecaller sends to MR | `Services/MessageService.php::sendAs` | ✅ |
| policy (either party; read own) | `Policies/MessagePolicy.php` | ✅ |
| MR inbox list | `Http/Controllers/Api/MessageController.php::index` | ✅ |
| unread count (polling) | `…::unreadCount` | ✅ |
| mark read | `…::markRead`, `…::markAllRead` | ✅ |
| admin message log + detail | `Http/Controllers/Web/MessageController.php` | ✅ |

## Notes
**No Firebase.** The MR app polls `unread-count` on resume and on a timer,
then loads the inbox. That endpoint is one indexed COUNT and nothing else;
`(to_user_id, read_at)` answers it from the index alone.

Read state is a single nullable `read_at` — the planned `is_read` flag beside
it was dropped, since two columns that must agree eventually do not. The API
still returns `is_read`, derived. A sender cannot set it: a message starts
unread by definition, and only its recipient may mark it.

One direction in Phase 5: a telecaller writes, an MR reads. Recipients are
**MRs covering a region the sender covers, plus the MR assigned to the person
being written about** — that one may sit elsewhere, and refusing them over a
region mismatch would be a dead end. `recipientsFor` backs both the picker and
validation, so the two cannot drift.

`person_id` / `call_log_id` are optional context, `nullOnDelete`: losing the
context must not delete what someone said. The panel scope is **either party**
— unlike calls, a message has two owners.

## Next
Phase 7 counts unread per MR on the dashboard. Replies are not in scope.
`MessagePolicy::markRead` is recipient-only — not the sender, not an admin.
