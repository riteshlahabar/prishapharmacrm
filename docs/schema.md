# Schema

## Live tables
`users` (name, email, password, mobile uniq, role `admin|mr|telecaller`,
  region_id→regions nullOnDelete — an MR's territory —, is_active)
`personal_access_tokens` (Sanctum); spatie and framework tables are stock
`regions` (name, code uniq, is_active)
`telecaller_region` (user_id, region_id — unique pair)
`client_types` (slug uniq, label, colour, has_specialization, is_active,
  sort_order) — what `persons.type` may hold; seeded by its own migration
`persons` (name, type → a `client_types.slug`, no FK — the API publishes
  that value; mobile uniq, email, address, city, specialization, region_id,
  assigned_mr_id, assigned_telecaller_id, is_active) — was `customers`
  (P2), "clients" in the panel
`products` (name uniq, box_qty — stock, admin-set only — cost, is_active)
`visit_products` / `call_log_products` (parent **cascade**, product_id
  **restrict**, box_qty, unit_cost — a snapshot) — unique per parent+product
`attendances` (mr_id→users cascade, date, check_in/out_at + lat/lng) —
  unique `(mr_id, date)`
`visits` (mr_id→users cascade, person_id→persons **restrict**, visit_date,
  purpose, in_time, out_time, lat, lng, location_fetched_at, notes) —
  indexed per rep and per person, by date
`call_logs` (telecaller_id→users cascade, person_id→persons **restrict**,
  called_at, status, notes, follow_up_at, **follow_up_done_at**) — indexed
  per caller, per person, and `(telecaller_id, follow_up_at)` for the queue
`messages` (from_user_id→users cascade, to_user_id→users cascade,
  person_id?→persons **null**, call_log_id?→call_logs **null**, subject,
  body, read_at — the read state, there is no `is_read`) — indexed per box
  and on `(to_user_id, read_at)`

## Rules
Geo `decimal(10,7)`, money `decimal(10,2)` — never float. Timestamps UTC,
server-set for attendance and visit receipt. Every FK indexed. A person is
`restrictOnDelete` from `visits` and a product from both line tables:
losing one must not erase the record.
