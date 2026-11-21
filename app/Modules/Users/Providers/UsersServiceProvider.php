<?php

namespace App\Modules\Users\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Users\Console\SetAdminCommand;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Users\Repositories\EloquentUserRepository;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use App\Modules\Users\Services\UserService;

class UsersServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Users';
    }

    /**
     * Contract => implementation; see docs/modules/users.md for status.
     */
    protected function bindings(): array
    {
        return [
            UserRepositoryInterface::class => EloquentUserRepository::class,
            UserServiceInterface::class => UserService::class,
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SetAdminCommand::class]);
        }
    }
}
