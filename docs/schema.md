# Schema

Legend: ✅ exists in MySQL now · ⬜ planned (phase in brackets)

## Live tables
`users` ✅ (name, email, password, mobile uniq, role `admin|mr|telecaller`
  nullable, region_id→regions nullOnDelete — an MR's single territory, is_active)
`personal_access_tokens` ✅ (Sanctum)
`roles` `permissions` `model_has_roles` `model_has_permissions`
`role_has_permissions` ✅ (spatie)
`cache` `cache_locks` `jobs` `job_batches` `failed_jobs` `sessions`
`password_reset_tokens` ✅ (framework)
`regions` ✅ (name, code uniq, is_active)
`telecaller_region` ✅ (user_id, region_id — unique pair)
`persons` ✅ (name, type, mobile uniq, email, address, city,
  specialization, region_id, assigned_mr_id, assigned_telecaller_id,
  is_active) — renamed from `customers` in P2
`products` `orders` — **dropped** in P2, out of scope

## Planned
`attendances` [P3] — id, mr_id→users, date, check_in_at, check_in_lat,
  check_in_lng, check_out_at, check_out_lat, check_out_lng
`visits` [P3] — id, mr_id→users, person_id→persons, visit_date, purpose,
  in_time, out_time, latitude, longitude, location_fetched_at, notes
`call_logs` [P4] — id, telecaller_id→users, person_id→persons, called_at,
  status(enum), notes, follow_up_at
`messages` [P5] — id, from_user_id→users, to_user_id→users, person_id?,
  call_log_id?, subject, body, is_read, read_at

## Rules
Geo: `decimal(10,7)` nullable. Timestamps UTC; server-set for attendance and
visit receipt. Every FK indexed; `visits(mr_id, visit_date)` and
`attendances(mr_id, date)` get composite indexes (unique on the latter).
