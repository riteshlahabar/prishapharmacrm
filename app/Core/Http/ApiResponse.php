<?php

namespace App\Core\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Single source of truth for the mobile API envelope. Both Flutter apps decode
 * exactly these shapes, so no controller may hand-roll a response.
 */
final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                // The size actually used, which is how an app sees that its
                // `per_page` was clamped (App\Core\Http\PerPage).
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                // Row span of this page, 1-based; null on an empty page.
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                // Saves the apps comparing page numbers to decide whether to
                // keep scrolling.
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
