# Module: Reports        Status: ✅ 6/6 — Phase 11
Paths: `app/Modules/Reports/` · Tables: **none**

## Functions
| Function | File | Status |
|---|---|---|
| report catalogue (columns, titles) | `Support/ReportDefinition.php` | ✅ |
| composition + clamping | `Services/ReportService.php` | ✅ |
| panel screens + CSV | `Http/Controllers/Web/ReportController.php` | ✅ |
| streamed CSV, injection-safe | `Support/CsvDownload.php` | ✅ |

## The six
`mr` presence, visits, coverage % · `telecaller` calls, conversion,
call-backs · `coverage` contacts nobody touched lately · `region` territory
rollup · `register` P/O/A grid · `follow-ups` promised vs honoured on time.

## Notes
This module owns **no table and runs no query**. Every figure arrives through
a sibling's `…ForUser` contract method, so the clamp stays with the module
owning the data — the Phase 7 dashboard pattern. The rollups added for it
(`perMrSummary`, `perTelecallerSummary`, `followUpCompliance`, `registerGrid`,
`countsByRegion`, `activeCountsByMr`, `sentCountsByUser`,
`lastVisitPerPerson`) live in the repository that owns each table.

Only clamped methods are exposed: there is no unclamped form a controller
could reach for by mistake, which is how the Phase 8 attendance bug happened.
Reps and regions clamp by **territory**; the telecaller report and follow-ups
clamp by **caller**, so a telecaller sees exactly one row — their own.

`ReportDefinition` drives the table head, the sort links and the CSV from one
list, so a column cannot be on screen but missing from the download.

`reports.view` opens the screens, `reports.export` downloads. Both grantable;
only `view` is a telecaller default, because a CSV leaves the panel and its
audit trail behind. Exports are `no-store` and prefix `=+-@` with a quote,
so a contact named `=cmd|…` cannot execute when Excel opens the file.

## Next
Done. Excel/PDF, scheduled e-mail and period comparison were deliberately
left out — see `docs/history/2026-11-phase11.md`.
