<?php

namespace App\Modules\Visits\Services;

use App\Modules\Visits\Services\Contracts\VisitPhotoStoreInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Visit photos on the **private** disk, under `visit-photos/YYYY/MM/`.
 *
 * Private on purpose. A shopfront photo says which MR was outside which
 * clinic and when, so it is served by `VisitPhotoController` behind the visit
 * policy — a public symlink would hand it to anyone who guessed the filename,
 * and would also need `storage:link` to survive every cPanel deploy.
 *
 * The name is a random ULID, never the device's: phones hand over names like
 * `IMG_20261124_104530.jpg`, which both collide and leak the shutter time
 * into a URL.
 */
class VisitPhotoStore implements VisitPhotoStoreInterface
{
    /** Where under the disk root the folders sit. */
    private const BASE = 'visit-photos';

    public function put(UploadedFile $photo): string
    {
        $extension = strtolower($photo->extension() ?: $photo->getClientOriginalExtension() ?: 'jpg');

        return $photo->storeAs(
            self::BASE.'/'.now()->format('Y/m'),
            Str::ulid().'.'.$extension,
            ['disk' => $this->disk()],
        );
    }

    public function delete(?string $path): bool
    {
        if (! $this->owns($path)) {
            return true;
        }

        return $this->files()->delete($path);
    }

    public function exists(?string $path): bool
    {
        return $this->owns($path) && $this->files()->exists($path);
    }

    public function response(string $path): StreamedResponse
    {
        // Inline, with its real type: the panel shows it in an <img>, and a
        // download prompt in the middle of a visit page would be wrong.
        return $this->files()->response($path, null, [
            'Content-Type' => $this->files()->mimeType($path) ?: 'application/octet-stream',
            // Browsers may cache it privately: the bytes never change, but a
            // shared proxy must not hold a territory-scoped file.
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /**
     * Guards every path that came off a row before it reaches the disk: a
     * value edited in the database to `../../.env` must not resolve.
     */
    private function owns(?string $path): bool
    {
        return $path !== null
            && $path !== ''
            && ! str_contains($path, '..')
            && str_starts_with($path, self::BASE.'/');
    }

    private function files(): Filesystem
    {
        return Storage::disk($this->disk());
    }

    private function disk(): string
    {
        return (string) config('filesystems.visit_photos', 'local');
    }
}
