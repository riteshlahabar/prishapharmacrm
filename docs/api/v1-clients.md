# API v1 — Add Client / My Clients     Status: ✅ done (Phase 13)

Shared by both apps. Envelope, errors, paging: **`v1-common.md`**.

| Method | Path | Ability | Purpose |
|---|---|---|---|
| GET | `/persons?mine=1` | mr **or** tc | My Clients — the rows you own |
| POST | `/persons` | mr **or** tc | Add Client |
| GET | `/client-types` | mr **or** tc | the type dropdown |
| GET | `/assignees/mrs` | tc | which MR to assign to |
| GET | `/assignees/telecallers` | mr | which telecaller to assign to |

## POST /persons
Whoever adds a client is one of its two assignees and may name the other.
The counterpart is optional: left out, the client is owned by the caller
alone until an admin assigns the other side from the panel.

Body: `name`, `type` (an active `client_types.slug`), `mobile` (unique),
optionally `email`, `city`, `address`, `specialization` — plus **the
counterpart, optional**: `assigned_mr_id` from the telecaller app,
`assigned_telecaller_id` from the MR app. Omit it to leave that side
unassigned; send it and it must hold that role and be active, else **422**.

Four things the body may not carry, each `prohibited` so sending one is a
422 naming the field:

- your own assignment — from the token, like `mr_id` on a visit;
- `region_id` — derived: it follows the **assigned MR**, who is the one with
  a territory. Null if that MR has none yet;
- `is_active` — a new client is active.

`specialization` is kept only for a type flagged `has_specialization`, same
as the panel. **201** with the full client, relations loaded.

## mine=1
Swaps the clamp from **territory to ownership**: assignee = you, wherever
the client sits. Not region-scoped on purpose — a counterpart may cover
another region, and that is the row the list exists to show. Active only;
the unflagged `GET /persons` is unchanged.

## The pickers
Plain arrays, no `meta`, unscoped by territory: assignment crosses them by
design and the field is required, so an empty list would make a client
unaddable. Rows carry `regions`. `/assignees/mrs` is **not** `/mrs`.
