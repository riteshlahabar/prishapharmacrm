# Module: Products       Status: ✅ — master + the lines on visits and calls
Paths: `app/Modules/Products/` · Tables: `products`

## Functions
| Function | File | Status |
|---|---|---|
| table (name, box_qty, cost) | `Database/Migrations/` | ✅ |
| admin CRUD, no `show` | `Http/Controllers/Web/ProductController` | ✅ |
| in-use guard on delete | `Services/ProductService.php` | ✅ |
| line pricing for siblings | `ProductService::priceLines()` | ✅ |
| `GET /api/v1/products` | `Http/Controllers/Api/ProductController` | ✅ |

## Notes
Three required fields: name (unique), **box_qty** — boxes on hand — and
**cost** per box. `box_qty` is the admin's number alone: recording a product
on a visit or a call does **not** move it. The apps report what was
discussed; this is not a dispatch system.

The API list is active products only and **unpaginated** — a short master an
app loads once a session. Gated on `abilities:role:mr,role:telecaller`, the
same way `/persons` is, because both apps read it.

`priceLines()` is why Visits and Calls depend on this contract: it turns
`[{product_id, box_qty}]` into rows carrying the cost of a box *as it is
now*. The snapshot is the point — a price change next month must not rewrite
a visit that already happened. It also drops unknown products and sums two
lines naming the same one, since the line tables are unique per product.

A product on any line cannot be deleted (`ProductInUse`, and
`restrictOnDelete` behind it). Deactivating is how one is retired: it leaves
both apps and every past record keeps it.

The `products` name is reused — the generic CRM this project started from had
one, dropped in Phase 2. The create migration drops any leftover first, so a
database that rolled that migration back is not left with the old columns.

## Next
Done. A per-product report (boxes by rep over a period) is the obvious
extension; nobody has asked for one.
