<?php

use App\Core\Http\ApiExceptionRenderer;
use App\Core\Http\Middleware\EnsureActiveApiUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Panel capability checks, e.g. permission:persons.view.
        // Plus Sanctum's token-ability gates, which the app endpoints use to
        // refuse a token minted for the other app: `ability:role:mr` requires
        // all of the listed abilities, `abilities:` any one of them.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'ability' => CheckAbilities::class,
            'abilities' => CheckForAnyAbility::class,
        ]);

        // What every authenticated API route groups on, in this order:
        // authenticate the token, then re-check the account behind it. Route
        // files use `api.auth` rather than `auth:sanctum` so a new endpoint
        // cannot pick up the first without the second.
        $middleware->group('api.auth', [
            'auth:sanctum',
            EnsureActiveApiUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Every failure on api/* answers in the ApiResponse envelope, so the
        // Flutter clients decode one shape for success and error alike.
        // Returns null off api/*, leaving the panel its HTML error pages.
        $exceptions->render(
            fn (Throwable $e, Request $request) => ApiExceptionRenderer::handle($e, $request),
        );
    })->create();
