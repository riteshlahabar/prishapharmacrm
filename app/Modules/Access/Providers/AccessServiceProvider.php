<?php

namespace App\Modules\Access\Providers;

use App\Core\Providers\ModuleServiceProvider;

class AccessServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Access';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/access.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
