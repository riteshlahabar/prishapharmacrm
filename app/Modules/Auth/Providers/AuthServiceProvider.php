<?php

namespace App\Modules\Auth\Providers;

use App\Core\Providers\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Auth';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/auth.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
