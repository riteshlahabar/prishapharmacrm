# Cuba theme — our patches to vendor files

`public/assets` is committed, so edits here are permanent; nothing re-downloads
over them. Keep this list complete, and comment each change `Prisha patch` in
place so it survives a later diff against stock Cuba.

## `css/style.css` — the primary colour
Cuba's primary purple is repainted in the Prisha teal: `#7366ff` → `#00797c`,
its tints and shades (`#988fff`, `#cfcdfc`, `#e8e5ff`, `#f5f4ff`, …) to the
matching teals, and every `rgb/rgba(115, 102, 255, …)` to `(0, 121, 124, …)`.
402 values in one pass, by `--theme-default` **and** by literal, because the
compiled file hardcodes the colour in 222 places that no variable reaches.
Bootstrap's own `#6610f2` and the demo widgets' unrelated violets are left
alone. Redo it with the same mapping if style.css is ever replaced.

## `js/sidebar-menu.js`
1. **Highlight survives detail pages.** The "page active" block cleared every
   `.active` class, then re-added it only for an href equal to the current
   path. On `/visits/5` or `/persons/3/edit` nothing matches, so the highlight
   the Blade sidebar had rendered was wiped. It now only takes over when a link
   really matches.
2. **No crash when nothing is active.** The scroll-into-view below it guarded
   on a jQuery object — always truthy, even for an empty set — then read
   `.offset().top` off it. Every page whose URL is not a sidebar href threw
   *Cannot read properties of undefined (reading 'top')*. It tests `.length`.
3. **No `null` class.** The layout line interpolated a missing `localStorage`
   value into the class attribute, so a first visit rendered
   `class="page-wrapper null"` before the default was appended.

## Not patched, worth knowing
`js/login.js` targets `input[name="login[password]"]`, a name Laravel
validation rejects; the login view ships its own handler instead of a patch
(`ui-assets.md`).
