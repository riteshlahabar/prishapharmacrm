# Module: Regions        Status: ✅ done (3/3) — Phase 2
Paths: `app/Modules/Regions/` · Tables: `regions`, `telecaller_region`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Region.php` | ✅ |
| admin CRUD | `Http/Controllers/Web/RegionController.php` | ✅ |
| assign regions to telecaller / MR | `Services/RegionAssignmentService.php` | ✅ |

## Notes
Region is the scoping key for the telecaller side: a telecaller sees only
persons in the regions admin assigned. Persons belong to exactly one region.

**Decided:** an MR covers one region (`users.region_id`), a telecaller covers
many (`telecaller_region` pivot) — a rep travels a territory, a phone list can
widen any time. `RegionAssignmentService` hides that split behind one
`assign()`, so Users never branches on role to save a region.

Deleting a region that MRs, telecallers or persons point at is refused
(`RegionInUse`); deactivating is the way to retire one. Both FKs onto
`regions` are `nullOnDelete`, as a backstop for anyone going in via SQL.
`code` is stored uppercase and is unique.

`code` is upper-cased in `prepareForValidation`, not just on save, so
`unique` tests the string that is actually stored (Phase 8 — on a
case-sensitive collation "dup" slipped past the rule and hit the index).

## Next
Done. Attendance, visits and calls all report by region.
