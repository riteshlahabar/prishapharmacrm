# API v1 — My Clients, the pickers    Status: ✅ done (Phase 13)

What both apps read from the client book; the writes are
**`v1-clients.md`**. Envelope, errors, paging: **`v1-common.md`**.

| Method | Path | Ability | Purpose |
|---|---|---|---|
| GET | `/persons?mine=1` | mr **or** tc | My Clients — the rows you own |
| GET | `/client-types` | mr **or** tc | the type dropdown |
| GET | `/assignees/mrs` | tc | which MR to assign to |
| GET | `/assignees/telecallers` | mr | which telecaller to assign to |

## mine=1
Swaps the clamp from **territory to ownership**: assignee = you, wherever
the client sits. Not region-scoped on purpose — a counterpart may cover
another region, and that is the row the list exists to show. Active only;
the unflagged `GET /persons` is unchanged.

The row carries everything the app needs to act on it without a second
call — there is no `GET /persons/{id}` — so the edit form opens on it and
the address goes straight to the phone's map app.

## The pickers
Plain arrays, no `meta`, unscoped by territory: assignment crosses them by
design, so a region filter would make a client unaddable. Rows carry
`regions`. `/assignees/mrs` is **not** `/mrs`.
