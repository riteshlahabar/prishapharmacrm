# Cuba theme — required markup contract

Cuba's CSS is complete in `public/assets` (tracked). A page that "looks
broken" is the markup or the stylesheet order, never a missing download.
Assets and load order: `ui-assets.md`.

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
~1828 rules nest under `.page-wrapper`, ~1140 under `.page-body-wrapper`;
`sidebar-menu.js` tests `#pageWrapper.compact-wrapper`, `sidebar-pin.js` needs
`.pin-title` + `.fa-thumbtack` per `.sidebar-list`, simplebar binds
`#simple-bar`. Drop a wrapper and the theme silently does nothing.

## Bootstrap 5 only
`.media` / `.media-body` are Bootstrap 4 and carry no rules here. The header
profile is `.d-flex.profile-media > img.img-30 + .flex-grow-1`; wrong markup
grows the header past 80px and hides the top of every page.

## Rules
- Layouts: compact-wrapper, horizontal-wrapper, compact-sidebar,
  compact-small, modern-type, material-type, advance-layout, box-layout.
- `data-sidebar-layout`: only `fill-svg` or `stroke-svg`.
- `.card-header` makes a direct `span` a block subtitle (fine for `.f-light`,
  a badge stretches — wrap it in a `div`) and title-cases its `h5`; its
  `:not(.mb-0), :not(.m-0)` opt-out is a selector list, so one class never
  escapes it; on user text set `text-transform: none`.
- Check a class exists before using it:
  `grep -c -F ".my-class" public/assets/css/style.css`

## Verify a page
`.sidebar-wrapper` is `position: fixed`, 265px wide; `.page-body` gets
`margin-left: 265px` and `margin-top: 80px`; `.page-header` is <= 80px.
