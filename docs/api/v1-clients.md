# API v1 — Add / Edit Client        Status: ✅ done (Phase 13)

What both apps write to the client book; what they read is
**`v1-clients-lists.md`**. Envelope, errors: **`v1-common.md`**.

| Method | Path | Ability | Purpose |
|---|---|---|---|
| POST | `/persons` | mr **or** tc | Add Client |
| PATCH | `/persons/{id}` | mr **or** tc | Edit Client — one you own |

## POST /persons
Whoever adds a client is one of its two assignees and may name the other.

Body: `name`, `type` (an active `client_types.slug`), `mobile` (unique),
optionally `email`, `city`, `address`, `specialization` — plus **the
counterpart, optional**: `assigned_mr_id` from the telecaller app,
`assigned_telecaller_id` from the MR app. Omit it to leave that side for an
admin; send it and it must hold that role and be active, else **422**.

Three fields are `prohibited`, so sending one is a 422 naming it:

- your own assignment — from the token, like `mr_id` on a visit;
- `region_id` — derived: it follows the **assigned MR**, who is the one with
  a territory. Null if that MR has none yet;
- `is_active` — a new client is active.

`specialization` is kept only for a type flagged `has_specialization`, same
as the panel. **201** with the full client, relations loaded.

## PATCH /persons/{id}
Correcting a client **you are an assignee on** — the `mine=1` clamp, so
every row My Clients shows is one this saves, and anyone else's client is a
**404**, never a 403.

Every field is optional: an absent key is unchanged, and one sent null or
empty clears that column. `mobile` is unique *ignoring this client*. The
counterpart may be changed or unassigned — the one assignment an app owns.
A type change takes the specialization with it, as on the panel.

POST's three prohibitions hold, joined by **your own** assignment: an MR
sending `assigned_mr_id` is a 422, because the client would leave the list
it was edited from. `region_id` never moves on an edit — it follows the
assigned MR, and that side is not the app's to change.
