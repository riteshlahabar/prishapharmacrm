<?php

namespace App\Core\Http;

use Illuminate\Http\Request;

/**
 * One page-size rule for every list endpoint.
 *
 * Before this, each controller read `per_page` straight off the request, so a
 * client could ask for 100000 rows in one page — and `per_page=-5` reached the
 * database as `offset 0` with no limit and returned a 500 from a raw SQL
 * syntax error. `per_page=0` quietly fell through to Eloquent's own default of
 * 15, which is not the 20 the docs promise.
 *
 * So: anything unusable becomes DEFAULT, anything oversized becomes MAX. The
 * value actually used is echoed in `meta.per_page`, which is how an app can
 * tell its request was clamped.
 */
final class PerPage
{
    /** What a list returns when the app does not ask (docs/api/v1-common.md). */
    public const DEFAULT = 20;

    /** Ceiling for one page, so a single request cannot pull the whole table. */
    public const MAX = 100;

    public static function from(Request $request, int $default = self::DEFAULT): int
    {
        return self::clamp($request->query('per_page'), $default);
    }

    public static function clamp(mixed $value, int $default = self::DEFAULT): int
    {
        // Covers null, "", "abc", "1,2" and arrays — all "the app did not ask".
        if (! is_numeric($value)) {
            return $default;
        }

        $perPage = (int) $value;

        // Zero and negatives are not a page size. Treat them as unasked
        // rather than erroring: a shipped app build cannot be fixed today.
        if ($perPage < 1) {
            return $default;
        }

        return min($perPage, self::MAX);
    }
}
