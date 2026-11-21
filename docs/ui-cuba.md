# Cuba theme — required markup contract

Cuba's CSS is **already complete** in `public/assets` (style.css 2.3 MB,
tracked in git). Nothing needs uploading. When pages "look broken" it is the
markup not matching the selectors, not a missing stylesheet.

## Non-negotiable nesting
```
.page-wrapper.compact-wrapper#pageWrapper
  .page-header > .header-wrapper.row
  .page-body-wrapper
     div.sidebar-wrapper
        .logo-wrapper / .logo-icon-wrapper
        nav.sidebar-main > #sidebar-menu > ul.sidebar-links#simple-bar
           li.sidebar-list > a.sidebar-link.sidebar-title + ul.sidebar-submenu
     .page-body  (supplied by each view's @section('content'))
        .container-fluid > .page-title > .row     <- direct child required
     footer.footer
```
Why it matters: style.css nests ~1828 rules under `.page-wrapper` and ~1140
under `.page-body-wrapper`; `sidebar-menu.js` branches on
`$("#pageWrapper").hasClass("compact-wrapper")`; `sidebar-pin.js` needs a
`.pin-title` element plus `.fa-thumbtack` per `.sidebar-list`; simplebar binds
to `#simple-bar`. Omit a wrapper and the theme silently does nothing.

## Rules
- Assets resolve as `asset('assets/...')` — the `assets/` prefix is required.
- Feather CSS lives at `assets/css/vendors/feather-icon.css`.
- Valid layout classes: compact-wrapper, horizontal-wrapper, compact-sidebar,
  compact-small, modern-type, material-type, advance-layout, box-layout.
- `data-sidebar-layout` accepts only `fill-svg` or `stroke-svg`.
- Logos present: `logo.png`, `logo_dark.png`, `logo-icon.png`. There is **no**
  `logo-white.png`.
- Before inventing a class, check it exists:
  `grep -c -F ".my-class" public/assets/css/style.css`

## Verify a page
`.sidebar-wrapper` must compute to `position: fixed`, width 265px, and
`.page-body` must get `margin-left: 265px`.
