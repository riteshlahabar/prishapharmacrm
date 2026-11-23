# Cuba theme — assets and load order

Companion to `ui-cuba.md` (markup). Everything here is in `public/assets`,
committed.

## Stylesheet order — `resources/views/layouts/css.blade.php`
1. `css/fontawesome.css`
2. Google Fonts (Inter, Open Sans)
3. **`css/vendors/bootstrap.css`** (Bootstrap 5.3.3)
4. `css/vendors/feather-icon.css`
5. `css/style.css` — Cuba
6. `css/responsive.css`
7. **`css/prisha.css`** — ours, and it has to stay last

Bootstrap must come before `style.css`. Cuba is a layer of overrides on it:
`.row`, `.col-*`, `.form-control`, `.table` and `.badge` exist only in
Bootstrap, and Cuba's `.btn-primary` sets colour without `display`. Omit
Bootstrap and the grid collapses into stacked blocks while buttons render
their label in the colour of their own background — the page looks styled,
so the cause is easy to miss.

## Scripts — `layouts/scripts.blade.php`
jQuery first, then Bootstrap's bundle, feather, simplebar, `config.js`,
`sidebar-menu.js`, then `@yield('scripts')`, then `script.js`. The layout
yields `scripts`; a view that pushes to a stack renders nothing.

`sidebar-pin.js` is **not** loaded: the sidebar has no thumbtacks and no
"Pinned" heading, and the script reads `.pin-title` into `.classList` on
load, so it throws on every page without one.

`assets/js/login.js` toggles `input[name="login[password]"]`, a name Laravel
validation rejects, so the login view ships its own four-line show/hide
handler against `#password` instead.

## Patched theme files
`js/sidebar-menu.js` and `css/style.css` — see `ui-theme-patches.md`.

## Paths
`asset('assets/...')` — the prefix is required; 42 calls once missed it.
Feather CSS is at `assets/css/vendors/feather-icon.css`.

## Branding
Exactly three logos exist — no `logo-white.png`. `images/logo/logo.png`
(wordmark), `logo_dark.png` (the same, lightened for the dark sidebar) and
`logo-icon.png` (the mark) carry the Prisha artwork; `images/favicon.png`
and `public/favicon.ico` are the mark too.

The brand layer itself is `ui-brand.md`.
