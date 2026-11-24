# Prisha Pharma — Master Index

**need → doc → code path.** Read this first; open only the doc it names.
Status: ✅ done · 🟡 partial · ⬜ pending · ⛔ blocked

## Projects
| Code | Project | Repo | Stack |
|---|---|---|---|
| CRM | Panel + mobile API | `prishapharmacrm` | Laravel 13, MySQL, Cuba |
| MR | Medical Rep app ✅ | `prishapharmamrapp` | Flutter |
| TC | Telecaller app ✅ | `prishapharmatellecallerapp` | Flutter |

## CRM modules — `app/Modules/<Name>/`
| Module | Status | Doc | Owns |
|---|---|---|---|
| Access | ✅ | `modules/access.md` | roles, permissions |
| Auth | ✅ | `modules/auth.md` | panel login, Sanctum tokens |
| Users | ✅ | `modules/users.md` | admin creates MR + TC |
| Regions | ✅ | `modules/regions.md` | region master, assignment |
| Persons | ✅ | `modules/persons.md` | clients (doctor, hospital…) |
| ClientTypes | ✅ | `modules/client-types.md` | editable type master |
| Attendance | ✅ | `modules/attendance.md` | MR check-in/out |
| Visits | ✅ | `modules/visits.md` | visit entry, times, geo |
| Calls | ✅ | `modules/calls.md` | call status + notes |
| Messages | ✅ | `modules/messages.md` | telecaller → MR msg |
| Dashboard | ✅ | `modules/dashboard.md` | tiles + charts |
| Reports | ✅ | `modules/reports.md` | 6 reports + CSV |

## Where do I find…?
| Need | Go to |
|---|---|
| Phases, what's next | `roadmap.md` |
| Tests, factories, demo data | `testing.md` |
| Table columns, relations | `schema.md` |
| SOLID / module rules | `conventions.md` |
| Cuba markup, broken layout | `ui-cuba.md` |
| Logos · brand CSS · actions | `ui-{assets,brand,actions}.md` |
| Patches to Cuba's files | `ui-theme-patches.md` |
| Envelope, errors, paging | `api/v1-common.md` |
| App login, app endpoints | `api/v1-auth.md` · `api/v1-{mr,telecaller}.md` |
| Permission names | `modules/access.md` |
| Shared base classes | `app/Core/` |
| 403 vs 404, policies | `authorization.md` |
| Module registry | `config/modules.php` |
| Older session log | `history/` |
