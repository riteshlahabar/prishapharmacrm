# API v1 — Telecaller app        Status: 🟡 calls + persons done (Phase 4)

Base `/api/v1` · Bearer token · `ability:role:telecaller`, so an MR token is
**403** on `/call-logs`. `/persons` is shared and takes either app's token.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/persons` | ✅ | the call queue — see below |
| POST | `/call-logs` | ✅ | person_id, status, notes, follow_up_at |
| GET | `/call-logs` | ✅ | own history (filters: status, person, dates) |
| GET | `/call-logs/follow-ups` | ✅ | due call-backs (`?due=0` for all open) |
| PATCH | `/call-logs/{id}` | ✅ | amend status, notes or the call-back |
| POST | `/call-logs/{id}/follow-up-done` | ✅ | close a call-back, no new call |
| POST | `/messages` | ⬜ P5 | send info to an MR |
| GET | `/mrs` | ⬜ P5 | MRs selectable as recipients |

## Persons
Scoped server-side by role — a telecaller gets their regions, an MR gets their
own assignments — so the app never sends a region filter it could tamper with.
Active persons only. For a telecaller each row carries `last_call`: the last
call **that caller** made on that person, in the same shape `/call-logs`
returns, or the key is absent if they never rang them.

## Call logs
`telecaller_id` and `called_at` come from the token and the server clock and
are never read from the body, so sending them changes nothing. Status: interested, not_interested, callback,
not_reachable, converted. `follow_up_at` is **required on `callback`, refused
on anything else**, and must be in the future → otherwise **422**. A person
outside the caller's regions is 422, not 403.
`follow_up_open` / `follow_up_due` are computed server-side so the queue badge
never depends on the device clock. Amending or closing another caller's log is
**404**, not 403.
