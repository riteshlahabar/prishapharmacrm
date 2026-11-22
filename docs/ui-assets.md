# Cuba theme — assets and load order

Companion to `ui-cuba.md`, which covers markup. Everything here lives in
`public/assets`, committed.

## Stylesheet order — `resources/views/layouts/css.blade.php`
1. `css/fontawesome.css`
2. Google Fonts (Inter, Open Sans)
3. **`css/vendors/bootstrap.css`** (Bootstrap 5.3.3)
4. `css/vendors/feather-icon.css`
5. `css/style.css` — Cuba
6. `css/responsive.css`

Bootstrap must come before `style.css`. Cuba is a layer of overrides on top of
it: `.row`, `.col-*`, `.form-control`, `.table` and `.badge` exist only in
Bootstrap, and Cuba's `.btn-primary` sets colour without `display`. Omit
Bootstrap and the grid collapses into stacked blocks while buttons render
their label in the colour of their own background — the page looks styled,
so the cause is easy to miss.

## Scripts — `layouts/scripts.blade.php`
jQuery first, then Bootstrap's bundle, feather, simplebar, `config.js`,
`sidebar-menu.js`, `sidebar-pin.js`, then `@yield('scripts')`, then
`script.js`. The layout yields `scripts`; a view that pushes to a stack
renders nothing.

`assets/js/login.js` toggles `input[name="login[password]"]`, which is not a
name Laravel validation accepts, so the login view ships its own four-line
show/hide handler against `#password` instead.

## Patched theme files
`js/sidebar-menu.js` carries three fixes — see `ui-theme-patches.md`.

## Paths
`asset('assets/...')` — the `assets/` prefix is required; 42 calls were once
missing it. Feather CSS is at `assets/css/vendors/feather-icon.css`.

## Branding
`images/logo/logo.png`, `logo_dark.png` and `logo-icon.png` are still the
stock Cuba artwork. Replacing them with Prisha artwork rebrands the panel and
the login page at once; no markup changes.
