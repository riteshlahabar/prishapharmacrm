<?php

namespace App\Core\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Turns a thrown exception into the same envelope ApiResponse produces, so a
 * failure on /api/* decodes exactly like a success does. Without this the apps
 * would need a second decoder: Laravel answers an expired token with a bare
 * {"message": "..."} and a validation error with {"message", "errors"} — both
 * missing `success`, which is the flag the Flutter clients branch on.
 *
 * Every branch also sets `errors.reason`: a stable machine-readable token the
 * apps can switch on, because user-facing `message` text is free to change.
 *
 * Registered for api/* only (bootstrap/app.php). The panel keeps Laravel's
 * HTML error pages.
 */
final class ApiExceptionRenderer
{
    /** Returns null for anything that is not an API request, so the default handler runs. */
    public static function handle(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        [$status, $message, $errors] = self::map($e);

        $response = ApiResponse::error($message, $errors, $status);

        // Keep transport headers the exception carried, e.g. Retry-After on a
        // throttled login, which the apps honour before retrying.
        if ($e instanceof HttpExceptionInterface) {
            $response->withHeaders($e->getHeaders());
        }

        return $response;
    }

    /** @return array{0: int, 1: string, 2: array<string, mixed>} */
    private static function map(Throwable $e): array
    {
        return match (true) {
            // Field errors stay as Laravel built them; only the envelope changes.
            $e instanceof ValidationException => [
                $e->status,
                $e->getMessage(),
                ['reason' => 'validation'] + $e->errors(),
            ],

            $e instanceof AuthenticationException => [
                401,
                'Unauthenticated.',
                ['reason' => 'unauthenticated'],
            ],

            // Sanctum's ability middleware raises this when a token minted for
            // the other app is used. "Invalid ability provided." told the app
            // nothing it could act on.
            $e instanceof AccessDeniedHttpException,
            $e instanceof AuthorizationException => [
                403,
                'This action is not allowed for your account.',
                ['reason' => 'forbidden'],
            ],

            // Deliberately indistinguishable from a row that belongs to
            // someone else: the services throw this instead of a 403 so the
            // response cannot confirm the row exists (docs/api/v1-common.md).
            // The default message named the model class and the id.
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => [
                404,
                'Not found.',
                ['reason' => 'not_found'],
            ],

            $e instanceof MethodNotAllowedHttpException => [
                405,
                'Method not allowed.',
                ['reason' => 'method_not_allowed'],
            ],

            // 429 from throttle:10,1 on login, and any other aborted status.
            $e instanceof HttpExceptionInterface => [
                $status = $e->getStatusCode(),
                $e->getMessage() !== '' ? $e->getMessage() : self::statusText($status),
                ['reason' => $status === 429 ? 'throttled' : 'http_error'],
            ],

            default => [
                500,
                'Server error.',
                // The class and message help locally; in production the
                // envelope stays generic and the detail goes to the log.
                config('app.debug')
                    ? ['reason' => 'server_error', 'exception' => $e::class, 'detail' => $e->getMessage()]
                    : ['reason' => 'server_error'],
            ],
        };
    }

    private static function statusText(int $status): string
    {
        return match ($status) {
            429 => 'Too many attempts. Try again shortly.',
            503 => 'Service unavailable.',
            default => 'Request failed.',
        };
    }
}
