<?php

namespace App\Modules\Products\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Products\Repositories\EloquentProductRepository;
use App\Modules\Products\Services\Contracts\ProductServiceInterface;
use App\Modules\Products\Services\ProductService;

class ProductsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Products';
    }

    /**
     * Contract => implementation; see docs/modules/products.md.
     */
    protected function bindings(): array
    {
        return [
            ProductRepositoryInterface::class => EloquentProductRepository::class,
            ProductServiceInterface::class => ProductService::class,
        ];
    }
}
