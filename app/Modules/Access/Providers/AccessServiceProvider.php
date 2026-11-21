<?php

namespace App\Modules\Access\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Access\Services\Contracts\PermissionServiceInterface;
use App\Modules\Access\Services\PermissionService;

class AccessServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Access';
    }

    /**
     * Contract => implementation; see docs/modules/access.md for status.
     */
    protected function bindings(): array
    {
        return [
            PermissionServiceInterface::class => PermissionService::class,
        ];
    }
}
