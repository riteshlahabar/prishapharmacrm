# API v1 — Telecaller app       Status: ✅ done (Phases 4–5)

Base `/api/v1` · Bearer · `ability:role:telecaller`, so an MR token is
**403** here. `/persons` and `/products` are shared — either app's token.
Envelope, errors, pagination: **`v1-common.md`**.

| Method | Path | Status | Purpose |
|---|---|---|---|
| GET | `/persons` | ✅ | the call queue — see below |
| GET | `/products` | ✅ | catalogue — **`v1-products.md`** |
| POST | `/call-logs` | ✅ | person_id, status, notes, follow_up_at, products |
| GET | `/call-logs` | ✅ | own history (status, person, dates) |
| GET | `/call-logs/follow-ups` | ✅ | due call-backs (`?due=0` = all open) |
| PATCH | `/call-logs/{id}` | ✅ | amend status, notes, call-back, lines |
| POST | `/call-logs/{id}/follow-up-done` | ✅ | close a call-back |
| POST | `/messages` · GET `/messages/sent` | ✅ | write to an MR; own sent |
| GET | `/mrs` | ✅ | recipient picker (`?person_id=` widens it) |

## Persons
Region-scoped server-side (active only), so the app never sends a filter it
could tamper with. Rows carry `last_call` — your last call on that person,
shaped as `/call-logs` returns, absent if you never rang.

## Call logs
`telecaller_id` and `called_at` come from the token and server clock, never
the body. Status: interested, not_interested, callback, not_reachable,
converted. `follow_up_at` is **required on `callback`, refused otherwise**
and must be future-dated → else **422**, as is a person outside your
regions. `follow_up_open` / `follow_up_due` are server-computed, so the
badge never trusts the device clock. Another caller's log reads **404**.

## Lists
Persons, call logs, follow-ups and sent alike, scoped to you. `/mrs` is a
plain array, no `meta`.

## Messages
One direction: you write, an MR reads. Recipients are MRs covering a region
you cover, **plus the MR assigned to the person you name** — `?person_id=`
widens the picker, and the same rule validates the send. `person_id` must
be in your regions, `call_log_id` your own, else **422**.
