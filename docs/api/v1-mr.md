# API v1 — MR app        Status: ⬜ pending (Phase 3/5/6)

| Method | Path | Purpose |
|---|---|---|
| POST | `/attendance/check-in` | lat/lng → server timestamps the row |
| POST | `/attendance/check-out` | lat/lng → closes today's row |
| GET | `/attendance/today` | current state for the home screen buttons |
| GET | `/persons` | persons assigned to this MR (filter: type, region) |
| POST | `/visits` | person_id, purpose, in_time, out_time, lat, lng |
| GET | `/visits` | own visit history (paginated) |
| GET | `/messages` | inbox from telecallers |
| GET | `/messages/unread-count` | polled — no Firebase |
| POST | `/messages/{id}/read` | mark read |

## Notes
Device sends coordinates only; the server sets times. Button state comes from
`/attendance/today` so a reinstall can't double check-in.
