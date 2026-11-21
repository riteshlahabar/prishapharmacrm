<?php

namespace App\Modules\Auth\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Auth';
    }

    /**
     * Contract => implementation; see docs/modules/auth.md for status.
     */
    protected function bindings(): array
    {
        return [
            AuthServiceInterface::class => AuthService::class,
        ];
    }
}
