# API v1 — Telecaller app        Status: ✅ done (Phases 4–5)

Base `/api/v1` · Bearer token · `ability:role:telecaller`, so an MR token is
**403** here. `/persons` is shared and takes either app's token.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/persons` | ✅ | the call queue — see below |
| POST | `/call-logs` | ✅ | person_id, status, notes, follow_up_at |
| GET | `/call-logs` | ✅ | own history (filters: status, person, dates) |
| GET | `/call-logs/follow-ups` | ✅ | due call-backs (`?due=0` for all open) |
| PATCH | `/call-logs/{id}` | ✅ | amend status, notes or the call-back |
| POST | `/call-logs/{id}/follow-up-done` | ✅ | close a call-back, no new call |
| POST | `/messages` | ✅ | to_user_id, subject, body, person/call refs |
| GET | `/messages/sent` | ✅ | what this telecaller has sent |
| GET | `/mrs` | ✅ | recipient picker (`?person_id=` widens it) |

## Persons
Region-scoped server-side (active only), so the app never sends a filter it
could tamper with. Each row carries `last_call` — the last call **you** made
on that person, shaped as `/call-logs` returns — or no such key if you never
rang them.

## Call logs
`telecaller_id` and `called_at` come from the token and the server clock and
are never read from the body. Status: interested, not_interested, callback,
not_reachable, converted. `follow_up_at` is **required on `callback`, refused
on anything else**, and must be in the future → else **422**, as is a person
outside your regions. `follow_up_open` / `follow_up_due` are computed
server-side, so the queue badge never trusts the device clock. Touching
another caller's log is **404**, not 403.

## Messages
One direction: you write, an MR reads. Recipients are MRs covering a region
you cover, **plus the MR assigned to the person you name** — `?person_id=`
widens the picker, and the same rule validates the send. `person_id` must be
in your regions, `call_log_id` must be your own call, else **422**. Read
state belongs to the recipient.
