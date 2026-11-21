<?php

namespace App\Modules\Dashboard\Providers;

use App\Core\Providers\ModuleServiceProvider;

class DashboardServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Dashboard';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/dashboard.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
