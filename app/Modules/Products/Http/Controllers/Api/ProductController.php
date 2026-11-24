<?php

namespace App\Modules\Products\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Products\Http\Resources\ProductResource;
use App\Modules\Products\Services\Contracts\ProductServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * `/api/v1/products` — the list both apps fill their product dropdown from.
 *
 * Active products only, and not paginated: this is a short master an app
 * loads once per session and holds in memory, so a page cursor would be
 * ceremony around a list of a few dozen rows. Stock (`box_qty`) rides along
 * so a rep can see what is on hand, but nothing either app does changes it.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductServiceInterface $products) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            ProductResource::collection($this->products->activeOptions())->resolve(),
            'Products loaded.',
        );
    }
}
