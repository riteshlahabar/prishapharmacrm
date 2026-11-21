<?php

namespace App\Modules\Regions\Providers;

use App\Core\Providers\ModuleServiceProvider;

class RegionsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Regions';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/regions.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
