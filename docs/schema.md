# Schema

## Live tables
`users` (name, email, password, mobile uniq, role `admin|mr|telecaller`
  nullable, region_id→regions nullOnDelete — an MR's territory, is_active)
`personal_access_tokens` (Sanctum)
spatie and framework tables (`roles`, `permissions`, `model_has_*`,
  `cache*`, `jobs*`, `sessions`, …) are stock
`regions` (name, code uniq, is_active)
`telecaller_region` (user_id, region_id — unique pair)
`client_types` (slug uniq, label, colour, has_specialization, is_active,
  sort_order) — what `persons.type` may hold; seeded by its migration
`persons` (name, type → a `client_types.slug`; no FK — the mobile API
  publishes that value; mobile uniq, email, address, city, specialization,
  region_id, assigned_mr_id, assigned_telecaller_id, is_active) — renamed
  from `customers` in P2 (`products`/`orders` were dropped then), and
  "clients" in the panel
`attendances` (mr_id→users cascade, date, check_in_at/lat/lng,
  check_out_at/lat/lng) — unique `(mr_id, date)`
`visits` (mr_id→users cascade, person_id→persons **restrict**, visit_date,
  purpose, in_time, out_time, latitude, longitude, location_fetched_at,
  notes) — indexed per rep and per person, by date
`call_logs` (telecaller_id→users cascade, person_id→persons **restrict**,
  called_at, status, notes, follow_up_at, **follow_up_done_at**) — indexes
  per caller and per person, plus `(telecaller_id, follow_up_at)` for the
  call-back queue
`messages` (from_user_id→users cascade, to_user_id→users cascade,
  person_id?→persons **null**, call_log_id?→call_logs **null**, subject,
  body, read_at) — indexed per box and on `(to_user_id, read_at)` for the
  unread poll. `read_at` alone is the read state.

## Rules
Geo: `decimal(10,7)` nullable. Timestamps UTC; server-set for attendance and
visit receipt. Every FK indexed. A person is `restrictOnDelete` from
`visits`: losing one must not erase the record of calling on them. A client
type cannot be deleted while a person carries its slug.
