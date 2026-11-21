# Schema

Legend: ✅ exists in MySQL now · ⬜ planned (phase in brackets)

## Live tables
`users` ✅ (name, email, password, **mobile** uniq, **role** `admin|mr|telecaller`
  nullable, **region_id** nullable (FK added with `regions` [P2]), **is_active**)
`personal_access_tokens` ✅ (Sanctum)
`roles` `permissions` `model_has_roles` `model_has_permissions`
`role_has_permissions` ✅ (spatie)
`cache` `cache_locks` `jobs` `job_batches` `failed_jobs` `sessions`
`password_reset_tokens` ✅ (framework)
`customers` ✅ → becomes `persons` [P2] · `products` `orders` ✅ → dropped [P2]

## Planned
`regions` [P2] — id, name, code, is_active
`persons` [P2] — id, name, type(doctor|hospital|medical), mobile, email,
  address, city, region_id→regions, specialization, assigned_mr_id→users,
  assigned_telecaller_id→users, is_active
`telecaller_region` [P2] — telecaller_id→users, region_id→regions (pivot)
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
