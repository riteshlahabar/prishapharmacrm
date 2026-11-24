<?php

namespace App\Modules\Visits\Services\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Where a visit's photo lives, and how it is handed back.
 *
 * Its own contract rather than Storage calls inside VisitService: the
 * service decides what a visit *is*, this decides where a file goes. Swapping
 * the private disk for S3 later is then a binding change, not a service edit.
 */
interface VisitPhotoStoreInterface
{
    /** Stores the upload and returns the path to keep on the row. */
    public function put(UploadedFile $photo): string;

    /** Quietly true when there was nothing to remove. */
    public function delete(?string $path): bool;

    public function exists(?string $path): bool;

    /** Inline response for the panel — never a public URL. */
    public function response(string $path): StreamedResponse;
}
