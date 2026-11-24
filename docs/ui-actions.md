# The action column

Every index table ends in `<td class="text-end">` holding one
`<div class="table-actions">` and nothing else. Rules in `prisha.css`.

## Shape
Cuba's own action column is `.product-action`: a flex row of 34px squares,
not a run of inline links. Ours is that, with the brand colours — 34px tile,
8px radius, 16px feather glyph, 6px gap, teal on `--prisha-surface`, filled
teal on hover.

```html
<td class="text-end">
    <div class="table-actions">
        <a class="btn btn-link btn-icon btn-sm" title="View" href="…">
            <i data-feather="eye"></i></a>
        <form class="d-inline" method="POST" action="…">
            @csrf @method('DELETE')
            <button class="btn btn-link btn-icon btn-icon-danger btn-sm"
                    type="submit" title="Delete">
                <i data-feather="trash-2"></i></button>
        </form>
    </div>
</td>
```

## Three things that bite
- **`.btn-link` underlines its content.** On an icon-only button that paints
  a rule under the glyph. `.btn.btn-icon` sets `text-decoration: none`.
- **The wrapper is not cosmetic.** Half these controls are `<a>` and half a
  `<button>` inside a `<form>`; an inline-flex box whose first child is an
  SVG takes its baseline from the glyph's bottom edge, so inline markup
  drops the form buttons half a line low and spaces them by whatever
  whitespace the Blade left. The flex row fixes both.
- **Never `.txt-danger` here.** Cuba declares it `color: #fc564a !important`,
  so no rule can give the glyph a white fill on hover. Delete is
  `.btn-icon-danger`.

## Sizes
Action tiles: 34px, 16px glyph. A `.map-pin` link is inline text, not a tile,
so it stays 18px. Feather renders at 24 and `.btn-sm` would take it to 14 —
both are overridden.

## A CSS change that will not show
Cuba is served off disk with no build step, so a browser caches `prisha.css`
and serves the old copy. `@assetv` (AppServiceProvider) appends the file's
mtime, so the URL changes when the file does. Still stale? Hard-reload once.
