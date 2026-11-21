<?php

namespace App\Modules\Auth\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Exceptions\AuthenticationFailed;
use App\Modules\Auth\Http\Requests\AppLoginRequest;
use App\Modules\Auth\Http\Resources\AuthUserResource;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/auth/*` for both Flutter apps. There is no registration endpoint —
 * the admin creates every account (docs/api/v1-auth.md).
 *
 * Every response goes through App\Core\Http\ApiResponse so the envelope the
 * apps decode is defined in exactly one place.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function login(AppLoginRequest $request): JsonResponse
    {
        try {
            ['token' => $token, 'user' => $user] = $this->auth->issueAppToken(
                $request->email(),
                $request->string('password')->value(),
                $request->expectedRole(),
                $request->deviceName(),
            );
        } catch (AuthenticationFailed $e) {
            return ApiResponse::error($e->getMessage(), ['reason' => $e->reason], 401);
        }

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ], 'Signed in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Signed out.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new AuthUserResource($request->user()),
            'Profile loaded.',
        );
    }
}
