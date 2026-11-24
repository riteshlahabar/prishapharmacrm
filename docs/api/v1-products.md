# API v1 — Products        Status: ✅

One endpoint and one request shape, shared by both apps.

| Method | Path | Ability | Purpose |
|---|---|---|---|
| GET | `/products` | either app's | the catalogue for both dropdowns |

Gated on `abilities:role:mr,role:telecaller` — either token passes, like
`/persons`. Active products only, **unpaginated**: it is a short master an
app loads once a session, so there is no page cursor.

A row is `{id, name, cost, box_qty, is_active}`. `cost` is a **string**,
the way the decimal column holds it: a price that round-trips through a
float can come back as 119.99999. `box_qty` is stock on hand, shown so a
rep knows what is available — nothing either app does changes it.

## Product lines
`products` is optional on `POST /visits`, `POST /call-logs` and
`PATCH /call-logs/{id}`:

```
"products": [{"product_id": 4, "box_qty": 2}]
```

At most 50 lines, each `box_qty` ≥ 1, each `product_id` one that exists —
else **422**. Omitting the key means "no products"; on a PATCH it means
"leave the lines alone", and `[]` clears them.

Each stored line keeps the cost of a box **as it was when recorded**. That
snapshot is the point: a price change next month must not rewrite a visit
that already happened, so a line's `unit_cost` can differ from the product's
`cost` today. Two lines naming the same product are summed.

Both the visit and the call-log responses carry the lines back as
`products: [{product_id, name, box_qty, unit_cost, line_total}]`.

Recording a product does **not** move its stock (`docs/modules/products.md`).
