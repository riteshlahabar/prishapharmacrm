<?php

namespace App\Modules\Calls\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Calls\Repositories\Contracts\CallLogRepositoryInterface;
use App\Modules\Calls\Repositories\EloquentCallLogRepository;
use App\Modules\Calls\Services\CallLogService;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;

class CallsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Calls';
    }

    /**
     * Contract => implementation; see docs/modules/calls.md for status.
     */
    protected function bindings(): array
    {
        return [
            CallLogRepositoryInterface::class => EloquentCallLogRepository::class,
            CallLogServiceInterface::class => CallLogService::class,
        ];
    }
}
