<?php

namespace App\Modules\Dashboard\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Dashboard\Services\Contracts\DashboardServiceInterface;
use App\Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Dashboard';
    }

    /**
     * Contract => implementation; see docs/modules/dashboard.md for status.
     * The module owns no repository: it reads other modules' services.
     */
    protected function bindings(): array
    {
        return [
            DashboardServiceInterface::class => DashboardService::class,
        ];
    }
}
