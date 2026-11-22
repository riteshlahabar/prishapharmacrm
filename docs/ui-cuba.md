# Cuba theme — required markup contract

Cuba's own CSS is complete in `public/assets` (tracked). When a page "looks
broken" it is the markup or the stylesheet order, never a missing download.
Stylesheets, scripts and asset paths: `ui-assets.md`.

## Non-negotiable nesting
```
.page-wrapper.compact-wrapper#pageWrapper
  .page-header > .header-wrapper.row            (must stay <= 80px tall)
  .page-body-wrapper
     div.sidebar-wrapper
        .logo-wrapper / .logo-icon-wrapper
        nav.sidebar-main > #sidebar-menu > ul.sidebar-links#simple-bar
           li.sidebar-list > a.sidebar-link.sidebar-title + ul.sidebar-submenu
     .page-body  (each view's @section('content'); margin-top is 80px)
        .container-fluid > .page-title > .row   <- direct child required
     footer.footer
```
~1828 rules nest under `.page-wrapper` and ~1140 under `.page-body-wrapper`;
`sidebar-menu.js` tests `#pageWrapper.compact-wrapper`, `sidebar-pin.js` needs
`.pin-title` plus `.fa-thumbtack` per `.sidebar-list`, simplebar binds
`#simple-bar`. Drop a wrapper and the theme silently does nothing.

## Bootstrap 5 only
`.media` / `.media-body` are Bootstrap 4 and carry no rules here. The header
profile is `.d-flex.profile-media > img.img-30 + .flex-grow-1`; get it wrong
and the header grows past 80px and hides the top of every page.

## Rules
- Valid layouts: compact-wrapper, horizontal-wrapper, compact-sidebar,
  compact-small, modern-type, material-type, advance-layout, box-layout.
- `data-sidebar-layout` accepts only `fill-svg` or `stroke-svg`.
- Logos: `logo.png`, `logo_dark.png`, `logo-icon.png`. No `logo-white.png`.
- A direct `span` child of `.card-header` renders as a block subtitle: fine
  for `.f-light`, but a badge there stretches. Wrap it in a `div`.
- Before inventing a class, check it exists:
  `grep -c -F ".my-class" public/assets/css/style.css`

## Verify a page
`.sidebar-wrapper` computes to `position: fixed`, width 265px; `.page-body`
gets `margin-left: 265px` and `margin-top: 80px`; `.page-header` is <= 80px.
