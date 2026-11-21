<?php

namespace App\Modules\Users\Providers;

use App\Core\Providers\ModuleServiceProvider;

class UsersServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Users';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/users.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
