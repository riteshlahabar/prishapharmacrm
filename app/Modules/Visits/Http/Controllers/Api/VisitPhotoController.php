<?php

namespace App\Modules\Visits\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Visits\Services\Contracts\VisitPhotoStoreInterface;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one visit's photo to the MR app — the same file the panel's
 * `Web\VisitPhotoController` serves, through the same store and the same
 * scope.
 *
 * The file stays on the private disk: this is a *token-authenticated* route,
 * not a public URL. The app asks for it with its bearer token like any other
 * endpoint, so a leaked path is worth nothing on its own. That is also why
 * the resource sends a URL rather than base64 bytes — a visit list of twenty
 * would otherwise carry twenty photos nobody has looked at yet.
 *
 * Resolved through `findForUser`, so the photo inherits the visit's scope
 * exactly: a visit the caller cannot read is a **404** here too, never a 403
 * that would confirm it exists (docs/authorization.md).
 */
class VisitPhotoController extends Controller
{
    public function __construct(
        private readonly VisitServiceInterface $visits,
        private readonly VisitPhotoStoreInterface $photos,
    ) {}

    public function __invoke(Request $request, int|string $visit): StreamedResponse
    {
        $row = $this->visits->findForUser($request->user(), $visit);

        // A row whose file is missing is a 404, not a broken stream — the
        // app then shows its "could not load" placeholder rather than half
        // an image.
        if (! $this->photos->exists($row->photo_path)) {
            throw new ModelNotFoundException;
        }

        return $this->photos->response($row->photo_path);
    }
}
