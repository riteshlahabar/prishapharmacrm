# Dark mode — the `body.dark-only` block

Cubas dark theme is `body.dark-only`, toggled by the moon in the header and
remembered in `localStorage["mode-cuba"]`. Its rules live in `style.css`,
which loads **before** `prisha.css` (`ui-brand.md`) — so every literal light
colour in the brand layer beat them. Shipped that way: the Medical Rep and
Telecaller screens, and every other list, kept a white striped table, a white
filter panel and near-black headings on a near-black page.

The block at the end of `prisha.css` repoints three brand tokens at Cubas
dark palette, which fixes every rule already written against a token:

| Token | Light | Dark |
|---|---|---|
| `--prisha-ink` | `#22313F` | `rgba(255,255,255,.82)` |
| `--prisha-surface` | `#F4F8F6` | `#22242e` |
| `--prisha-line` | `#E4ECE7` | `#374558` |

Cubas own dark palette: `#1d1e26` page, `#262932` card, `#374558` line.
The rest of the block cancels the rules that hardcoded a hex — table stripes
and hover, filter-bar fields, the pager, `.btn-light`, the icon tiles and
washes, the badge and mini-card accents, the muted greys.

## What does not change
`--prisha-teal` and `--prisha-green` stay. The table head, the active page and
the dashboard tiles are white-on-gradient and read on either background.
Accent *text* is different — `#00797C` on `#262932` is under 3:1 — so those
few rules take `--prisha-accent` (`#3BBFC2`), defined in the dark block only.

## Rules
Keep the block **last** in the file, and give any new light colour above it a
counterpart inside it. `tests/Feature/DarkModeCssTest.php` enforces both.
