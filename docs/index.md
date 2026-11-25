# Prisha Pharma — Master Index

**need → doc → code path.** Read this first; open only the doc it names.

## Projects
| Code | Project | Repo | Stack |
|---|---|---|---|
| CRM | Panel + API | `prishapharmacrm` | Laravel 13, MySQL, Cuba |
| MR | Medical Rep app ✅ | `prishapharmamrapp` | Flutter |
| TC | Telecaller app ✅ | `prishapharmatellecallerapp` | Flutter |

## CRM modules — `app/Modules/<Name>/`
| Module | Status | Doc | Owns |
|---|---|---|---|
| Access · Auth | ✅ | `modules/{access,auth}.md` | roles, rights, login |
| Users | ✅ | `modules/users.md` | admin creates MR + TC |
| Regions | ✅ | `modules/regions.md` | region master, assignment |
| Persons | ✅ | `modules/persons.md` | clients (doctor, hospital) |
| ClientTypes | ✅ | `modules/client-types.md` | editable type master |
| Products | ✅ | `modules/products.md` | products, visit/call lines |
| Attendance | ✅ | `modules/attendance.md` | MR check-in/out |
| Visits | ✅ | `modules/visits.md` | visit entry, times, geo, photo |
| Calls | ✅ | `modules/calls.md` | call status + notes |
| Messages | ✅ | `modules/messages.md` | telecaller → MR |
| Dashboard · Reports | ✅ | `modules/{dashboard,reports}.md` | tiles, reports, CSV |

## Where do I find…?
| Need | Go to |
|---|---|
| Phases, next | `roadmap.md` |
| Tests, factories, demo | `testing.md` |
| Table columns, relations | `schema.md` |
| SOLID, module rules | `conventions.md` |
| Visit photo · map in a view | `visit-photos.md` |
| Cuba markup, layout | `ui-cuba.md` |
| Logos · brand CSS · actions | `ui-{assets,brand,actions}.md` |
| Patches to Cuba | `ui-theme-patches.md` |
| Envelope, errors, paging | `api/v1-common.md` |
| App login, endpoints | `api/v1-auth.md` · `api/v1-{mr,telecaller}.md` |
| Clients · products from an app | `api/v1-{clients,products}*.md` |
| Permission names | `modules/access.md` |
| Base classes | `app/Core/` |
| 403 vs 404, policies | `authorization.md` |
| Module registry | `config/modules.php` |
| Older session log | `history/` |
