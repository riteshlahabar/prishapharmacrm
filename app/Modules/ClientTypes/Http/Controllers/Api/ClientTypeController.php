<?php

namespace App\Modules\ClientTypes\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\ClientTypes\Http\Resources\ClientTypeResource;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * `/api/v1/client-types` — the type dropdown both Add Client forms show.
 *
 * Active types only, in the master's own order, which is the same list the
 * panel's client form offers: adding a type in the panel makes it postable
 * from both apps with no release (docs/modules/client-types.md).
 */
class ClientTypeController extends Controller
{
    public function __construct(private readonly ClientTypeServiceInterface $types) {}

    /** A plain array, no `meta` — a small picker (docs/api/v1-common.md). */
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            ClientTypeResource::collection($this->types->activeOptions())->resolve(),
            'Client types loaded.',
        );
    }
}
