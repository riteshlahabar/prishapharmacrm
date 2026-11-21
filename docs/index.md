# Prisha Pharma — Master Index

Fast lookup: **need → doc → code path.** Read this file first; open only the
one doc it points at. Status: ✅ done · 🟡 partial · ⬜ pending · ⛔ blocked

## Projects
| Code | Project | Repo | Stack |
|---|---|---|---|
| CRM | Admin panel + mobile API | `prishapharmacrm` | Laravel 13, MySQL, Cuba theme |
| MR | Medical Rep app | `prishapharmamrapp` | Flutter, Riverpod, ProKit |
| TC | Telecaller app | `prishapharmatellecallerapp` | Flutter, Riverpod, ProKit |

## CRM modules — `app/Modules/<Name>/`
| Module | Status | Doc | Owns |
|---|---|---|---|
| Access | ✅ | `modules/access.md` | roles, permissions, telecaller scoping |
| Auth | ✅ | `modules/auth.md` | panel login, Sanctum tokens for apps |
| Users | ✅ | `modules/users.md` | admin creates MR + telecaller |
| Regions | ⬜ | `modules/regions.md` | region master, assignment |
| Persons | ⬜ | `modules/persons.md` | doctor / hospital / medical |
| Attendance | ⬜ | `modules/attendance.md` | MR check-in / check-out + geo |
| Visits | ⬜ | `modules/visits.md` | visit entry, in/out time, geo |
| Calls | ⬜ | `modules/calls.md` | call status + notes |
| Messages | ⬜ | `modules/messages.md` | telecaller → MR in-app msg |
| Dashboard | ⬜ | `modules/dashboard.md` | admin + telecaller dashboards |

## Where do I find…?
| Need | Go to |
|---|---|
| Phases, what's next | `roadmap.md` |
| Table columns, relations | `schema.md` |
| SOLID / module rules, API envelope | `conventions.md` |
| Cuba markup contract, broken layout | `ui-cuba.md` |
| Stylesheet / script load order, logos | `ui-assets.md` |
| App login endpoint | `api/v1-auth.md` |
| MR app endpoints | `api/v1-mr.md` |
| Telecaller app endpoints | `api/v1-telecaller.md` |
| Permission names, who may grant | `modules/access.md` → `Access/Support/PermissionCatalog.php` |
| Shared base classes | `app/Core/` (repo, service, ApiResponse) |
| Module registry | `config/modules.php` |
| Older session log | `history/` |
