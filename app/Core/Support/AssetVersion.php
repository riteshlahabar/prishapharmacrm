<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * A cache-busting URL for one of our own files under public/.
 *
 * Cuba's stylesheets are served straight off disk — no build step, no Vite
 * manifest — so `asset()` emits a bare path, and a browser that has cached
 * `prisha.css` keeps serving the old copy after an edit: a CSS fix looks like
 * it never happened until someone hard-reloads. Appending the file's mtime
 * makes the URL change whenever the file does.
 *
 * Only for the files we actually edit (`prisha.css`, the patched `style.css`
 * — see ui-theme-patches.md). Cuba's vendor files never change, so versioning
 * them would buy nothing but a stat call per request.
 */
final class AssetVersion
{
    /**
     * @param  string  $path  public-relative, e.g. "assets/css/prisha.css"
     */
    public static function url(string $path): string
    {
        $url = asset($path);
        $full = public_path($path);

        // A missing file is not worth an exception from inside a layout: the
        // <link> 404s either way, and the plain URL still points at it.
        if (! is_file($full)) {
            return $url;
        }

        $stamp = filemtime($full);

        return $stamp === false ? $url : $url.'?v='.$stamp;
    }
}
