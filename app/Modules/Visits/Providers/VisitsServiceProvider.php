<?php

namespace App\Modules\Visits\Providers;

use App\Core\Providers\ModuleServiceProvider;

class VisitsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Visits';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/visits.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
