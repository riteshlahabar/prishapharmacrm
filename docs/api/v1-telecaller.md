# API v1 — Telecaller app        Status: ✅ done (Phases 4–5)

Base `/api/v1` · Bearer token · `ability:role:telecaller`, so an MR token is
**403** here. `/persons` is shared, either app's token. Envelope, errors and
pagination: **`v1-common.md`**.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/persons` | ✅ | the call queue — see below |
| POST | `/call-logs` | ✅ | person_id, status, notes, follow_up_at |
| GET | `/call-logs` | ✅ | own history (status, person, dates) |
| GET | `/call-logs/follow-ups` | ✅ | due call-backs (`?due=0` for all open) |
| PATCH | `/call-logs/{id}` | ✅ | amend status, notes or the call-back |
| POST | `/call-logs/{id}/follow-up-done` | ✅ | close a call-back |
| POST | `/messages` | ✅ | to_user_id, subject, body, refs |
| GET | `/messages/sent` | ✅ | what this telecaller has sent |
| GET | `/mrs` | ✅ | recipient picker (`?person_id=` widens it) |

## Persons
Region-scoped server-side (active only), so the app never sends a filter it
could tamper with. Rows carry `last_call` — your last call on that
person, shaped as `/call-logs` returns — absent if you never rang.

## Call logs
`telecaller_id` and `called_at` come from the token and server clock, never
the body. Status: interested, not_interested, callback, not_reachable,
converted. `follow_up_at` is **required on `callback`, refused otherwise**,
and must be future-dated → else **422**, as is a person outside your regions.
`follow_up_open` / `follow_up_due` are server-computed, so the queue badge
never trusts the device clock. Another caller's log is **404**, not 403.

## Lists
Persons, call logs, follow-ups and sent page alike, scoped to you.
`/mrs` is a plain array, no `meta`.

## Messages
One direction: you write, an MR reads. Recipients are MRs covering a region
you cover, **plus the MR assigned to the person you name** — `?person_id=`
widens the picker, and the same rule validates the send. `person_id` must be
in your regions and `call_log_id` your own, else **422**.
