<?php

namespace App\Modules\ClientTypes\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\ClientTypes\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientTypes\Repositories\EloquentClientTypeRepository;
use App\Modules\ClientTypes\Services\ClientTypeService;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;

class ClientTypesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'ClientTypes';
    }

    /**
     * Contract => implementation; see docs/modules/client-types.md.
     *
     * The service is a singleton: it memoises the (tiny, rarely written)
     * table for the request, so rendering a page of clients costs one read
     * of it rather than one per row.
     */
    protected function bindings(): array
    {
        return [
            ClientTypeRepositoryInterface::class => EloquentClientTypeRepository::class,
        ];
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ClientTypeServiceInterface::class, ClientTypeService::class);
    }
}
