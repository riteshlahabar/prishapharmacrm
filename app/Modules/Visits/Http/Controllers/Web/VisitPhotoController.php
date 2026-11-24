<?php

namespace App\Modules\Visits\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Visits\Services\Contracts\VisitPhotoStoreInterface;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one visit's photo to the panel.
 *
 * The file is on a private disk, so this is the only way to it. The visit is
 * resolved through `findForUser` first, which means the photo inherits the
 * visit's territory scope exactly: a telecaller who cannot open the visit
 * cannot fetch its photo either, and gets the same **404** rather than a
 * 403 that would confirm the visit exists (docs/authorization.md).
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

        // A row whose file is missing is a 404, not a broken stream: the
        // panel's <img> then shows its alt text instead of half an image.
        if (! $this->photos->exists($row->photo_path)) {
            throw new ModelNotFoundException;
        }

        return $this->photos->response($row->photo_path);
    }
}
