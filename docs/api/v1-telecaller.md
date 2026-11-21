# API v1 — Telecaller app        Status: ⬜ pending (Phase 4/5/6)

| Method | Path | Purpose |
|---|---|---|
| GET | `/persons` | persons in this telecaller's assigned regions |
| POST | `/call-logs` | person_id, status, notes, follow_up_at |
| GET | `/call-logs` | own history (paginated, filter by status) |
| GET | `/call-logs/follow-ups` | due call-backs |
| POST | `/messages` | send info to an MR (to_user_id, person_id?, body) |
| GET | `/mrs` | MRs selectable as message recipients |

## Notes
Person list is region-scoped server-side — the app never sends a region filter
it could tamper with. Status enum: interested, not_interested, callback,
not_reachable, converted.
