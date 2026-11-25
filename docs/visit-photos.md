# Visit photo · and the map in the panel

## The photo
One optional picture per visit, taken in the MR app (camera first, gallery
as fallback), shrunk on the device to 1600px / q70 — usually 200–500 KB.

It travels **in the same POST** as the visit, so the request becomes
`multipart/form-data`: the usual fields plus `photo` (≤ 8 MB,
jpeg/png/webp) and `photo_taken_at`, with `products` flattened to
`products[0][product_id]`. JSON still works when there is no photo. One
request, because a second upload call could fail and leave a visit with no
picture and no sign of it. The reply reports `has_photo` and `photo_url` —
a link to the token-scoped route below, never a public one, and null when
there is no photo so a client never requests a certain 404.

`visits.photo_path` + `photo_taken_at` only. The file lands on the **private**
disk (`config('filesystems.visit_photos')`, default `local` →
`storage/app/private/visit-photos/YYYY/MM/<ULID>.jpg`), written by
`VisitPhotoStore`, which also refuses any stored path outside that folder.

Private because the photo says which rep stood outside which clinic, and
when. Two routes read it, and **both** resolve the visit with `findForUser`
first — so the photo inherits the visit's territory scope exactly and 404s
outside it, rather than 403ing and confirming the visit exists:

| Route | For | Auth |
| --- | --- | --- |
| `GET /visits/{id}/photo` (`visits.photo`) | the panel | session |
| `GET /api/v1/visits/{id}/photo` (`api.v1.visits.photo`) | the MR app's visit detail screen | bearer token |

Neither is a public URL and the file never leaves the private disk: the app
sends its token with the image request like any other call, so the link in
`photo_url` opens nothing on its own. Deleting the visit deletes the file.

One client-side caveat worth knowing: a browser's `<img>` ignores request
headers, so the photo does not load on the Flutter **web** build. The apps
ship to Android; the web build only proves they compile.

**Deploy:** set `upload_max_filesize` and `post_max_size` ≥ 10M in cPanel's
PHP options, or a large photo makes PHP drop the whole request body and the
rep sees a validation error about the *person*, not the photo. No
`storage:link` needed, and nothing to back up but `storage/app/private`.

## The map
The detail page embeds **OpenStreetMap** in an iframe
(`Visit::mapEmbedUrl()`): no API key, no billing account, nothing secret in
the HTML. It needs the browser to reach `openstreetmap.org` — on a closed
network the frame is blank and the "Open in Google Maps" link beside it still
works.

To switch to Google's tiles, return a Maps Embed API URL from that one
method, with the key in config, never in the Blade. Leaflet + OSM tiles is
the third option: zoomable, but adds a JS/CSS library to the panel.
