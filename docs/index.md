# Prisha Pharma — Master Index

**need → doc → code path.** Read this first; open only the doc it names.

## Projects
| Code | Project | Repo | Stack |
|---|---|---|---|
| CRM | Panel + mobile API | `prishapharmacrm` | Laravel 13, MySQL, Cuba |
| MR | Medical Rep app ✅ | `prishapharmamrapp` | Flutter |
| TC | Telecaller app ✅ | `prishapharmatellecallerapp` | Flutter |

## CRM modules — `app/Modules/<Name>/`
| Module | Status | Doc | Owns |
|---|---|---|---|
| Access · Auth | ✅ | `modules/{access,auth}.md` | roles, rights, login |
| Users | ✅ | `modules/users.md` | admin creates MR + TC |
| Regions | ✅ | `modules/regions.md` | region master, assignment |
| Persons | ✅ | `modules/persons.md` | clients (doctor, hospital…) |
| ClientTypes | ✅ | `modules/client-types.md` | editable type master |
| Products | ✅ | `modules/products.md` | products + visit/call lines |
| Attendance | ✅ | `modules/attendance.md` | MR check-in/out |
| Visits | ✅ | `modules/visits.md` | visit entry, times, geo |
| Calls | ✅ | `modules/calls.md` | call status + notes |
| Messages | ✅ | `modules/messages.md` | telecaller → MR |
| Dashboard · Reports | ✅ | `modules/{dashboard,reports}.md` | tiles, 6 reports, CSV |

## Where do I find…?
| Need | Go to |
|---|---|
| Phases, what's next | `roadmap.md` |
| Tests, factories, demo data | `testing.md` |
| Table columns, relations | `schema.md` |
| SOLID / module rules | `conventions.md` |
| Cuba markup, layout | `ui-cuba.md` |
| Logos, assets · brand CSS | `ui-assets.md` · `ui-brand.md` |
| Patches to Cuba's own files | `ui-theme-patches.md` |
| Envelope, errors, paging | `api/v1-common.md` |
| App login, app endpoints | `api/v1-auth.md` · `api/v1-{mr,telecaller}.md` |
| Products endpoint + lines | `api/v1-products.md` |
| Permission names | `modules/access.md` |
| Shared base classes | `app/Core/` |
| 403 vs 404, policies | `authorization.md` |
| Module registry | `config/modules.php` |
| Older session log | `history/` |
