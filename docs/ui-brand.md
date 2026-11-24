# The brand layer — `public/assets/css/prisha.css`

Loaded last, after Cuba's `style.css` and `responsive.css` (`ui-assets.md`).
Everything in it overrides a selector Bootstrap or Cuba already styles;
nothing redefines a component from scratch. Colours: `--prisha-teal`
`#00797C` and `--prisha-green` `#6EAE40`, the two in the logo.

| What | Why it is here |
|---|---|
| `.logo-wrapper img` width | the artwork is 2.1:1, the stock Cuba file 3.5:1 |
| `.table > thead > tr` gradient | on the **row**; per cell it reads as stripes |
| `.table-foot` | the count and the pager on one line under every table |
| `.pagination` | Bootstrap-5 markup from `partials/pagination-links` |
| `.btn` flex row | feather's 24px SVG drags a button's label to a second line |
| `.table-actions`, `.btn-icon` | the action column — `ui-actions.md` |
| `.card-toolbar`, `.filter-bar` | heading + actions on one baseline; filters in a tinted panel |
| `.mini-stats` | a report's bucket counts, one small card each, on one row |
| `.stat-tile.tile-*` | the six dashboard accents; the name comes from DashboardService |
| `@assetv` | mtime on style.css + prisha.css, or a browser serves a stale copy |
| `body.dark-only` | dark mode — `ui-dark.md` |

## Table head and Bootstrap 5.3
A cell's stripe and hover are an `inset 0 0 0 9999px` box-shadow, not a
background, so the header row needs `box-shadow: none` and a transparent
`background-color` on `th` for the gradient to show at all.

## Sizes
Logos: 132px in the sidebar, 150px on the login card — the artwork is 420px
natural, which turns both into a banner. Table and action-column icon sizes
live in `ui-actions.md`.

## Pagination
`AppServiceProvider` calls `Paginator::useBootstrapFive()` and points
`defaultView()` at `partials/pagination-links`. Laravel renders Tailwind
markup out of the box, which lands in a Bootstrap panel as a bare column of
links. `partials/pagination.blade.php` wraps the pager with the row count
and calls `withQueryString()` — without it page 2 drops every filter.
