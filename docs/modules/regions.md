# Module: Regions        Status: ⬜ pending (0/3) — Phase 2
Paths: `app/Modules/Regions/` · Tables: `regions`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Region.php` | ⬜ |
| admin CRUD | `Http/Controllers/Web/RegionController.php` | ⬜ |
| assign regions to telecaller / MR | `Services/RegionAssignmentService.php` | ⬜ |

## Notes
Region is the scoping key for the telecaller app: a telecaller sees only
persons in regions admin assigned. Persons belong to exactly one region.

## Next
Decide single vs many regions per telecaller (default: many, pivot table).
