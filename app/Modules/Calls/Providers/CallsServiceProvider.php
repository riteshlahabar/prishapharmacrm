<?php

namespace App\Modules\Calls\Providers;

use App\Core\Providers\ModuleServiceProvider;

class CallsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Calls';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/calls.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
