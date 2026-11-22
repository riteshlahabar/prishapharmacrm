# Schema

Every table below exists in MySQL now; nothing is left planned.

## Live tables
`users` (name, email, password, mobile uniq, role `admin|mr|telecaller`
  nullable, region_id→regions nullOnDelete — an MR's single territory, is_active)
`personal_access_tokens` (Sanctum)
`roles` `permissions` `model_has_roles` `model_has_permissions`
`role_has_permissions` (spatie)
`cache` `cache_locks` `jobs` `job_batches` `failed_jobs` `sessions`
`password_reset_tokens` (framework)
`regions` (name, code uniq, is_active)
`telecaller_region` (user_id, region_id — unique pair)
`persons` (name, type, mobile uniq, email, address, city,
  specialization, region_id, assigned_mr_id, assigned_telecaller_id,
  is_active) — renamed from `customers` in P2
`products` `orders` — **dropped** in P2, out of scope
`attendances` (mr_id→users cascade, date, check_in_at/lat/lng,
  check_out_at/lat/lng) — unique `(mr_id, date)`
`visits` (mr_id→users cascade, person_id→persons **restrict**, visit_date,
  purpose, in_time, out_time, latitude, longitude, location_fetched_at,
  notes) — indexes on `(mr_id, visit_date)` and `(person_id, visit_date)`
`call_logs` (telecaller_id→users cascade, person_id→persons **restrict**,
  called_at, status, notes, follow_up_at, **follow_up_done_at**) — indexes on
  `(telecaller_id, called_at)`, `(person_id, called_at)` and
  `(telecaller_id, follow_up_at)` for the call-back queue
`messages` (from_user_id→users cascade, to_user_id→users cascade,
  person_id?→persons **null**, call_log_id?→call_logs **null**, subject, body,
  read_at) — indexes on `(to_user_id, created_at)`, `(from_user_id,
  created_at)` and `(to_user_id, read_at)` for the unread poll. The planned
  `is_read` flag was dropped; `read_at` alone is the state.

## Rules
Geo: `decimal(10,7)` nullable. Timestamps UTC; server-set for attendance and
visit receipt. Every FK indexed. A person is `restrictOnDelete` from `visits`:
losing a person must not erase the history of having called on them.
