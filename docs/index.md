# Prisha Pharma — Master Index

Fast lookup: **need → doc → code path.** Read this first; open only the one
doc it points at. Status: ✅ done · 🟡 partial · ⬜ pending · ⛔ blocked

## Projects
| Code | Project | Repo | Stack |
|---|---|---|---|
| CRM | Panel + mobile API | `prishapharmacrm` | Laravel 13, MySQL, Cuba |
| MR | Medical Rep app ✅ | `prishapharmamrapp` | Flutter, Riverpod, ProKit |
| TC | Telecaller app ✅ | `prishapharmatellecallerapp` | Flutter, Riverpod, ProKit |

## CRM modules — `app/Modules/<Name>/`
| Module | Status | Doc | Owns |
|---|---|---|---|
| Access | ✅ | `modules/access.md` | roles, permissions, tc scoping |
| Auth | ✅ | `modules/auth.md` | panel login, Sanctum app tokens |
| Users | ✅ | `modules/users.md` | admin creates MR + telecaller |
| Regions | ✅ | `modules/regions.md` | region master, assignment |
| Persons | ✅ | `modules/persons.md` | doctor / hospital / medical |
| Attendance | ✅ | `modules/attendance.md` | MR check-in/out + geo |
| Visits | ✅ | `modules/visits.md` | visit entry, in/out time, geo |
| Calls | ✅ | `modules/calls.md` | call status + notes |
| Messages | ✅ | `modules/messages.md` | telecaller → MR in-app msg |
| Dashboard | ✅ | `modules/dashboard.md` | admin + telecaller cards |

## Where do I find…?
| Need | Go to |
|---|---|
| Phases, what's next | `roadmap.md` |
| Tests, factories, demo data | `testing.md` |
| Table columns, relations | `schema.md` |
| SOLID / module rules, envelope | `conventions.md` |
| Cuba markup, broken layout | `ui-cuba.md` |
| Asset load order, logos | `ui-assets.md` |
| Theme file patches | `ui-theme-patches.md` |
| Envelope, errors, paging | `api/v1-common.md` |
| App login endpoint | `api/v1-auth.md` |
| MR app endpoints | `api/v1-mr.md` |
| Telecaller endpoints | `api/v1-telecaller.md` |
| Permission names | `modules/access.md` |
| Shared base classes | `app/Core/` |
| 403 vs 404, policies | `authorization.md` |
| Module registry | `config/modules.php` |
| Older session log | `history/` |
