<?php

namespace App\Modules\Reports\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Reports\Services\Contracts\ReportServiceInterface;
use App\Modules\Reports\Services\ReportService;

/**
 * Reports owns **no table**. Every figure comes from another module's service
 * contract, so this module stays a composer of other people's numbers rather
 * than a second place where business rules live (docs/conventions.md).
 */
class ReportsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Reports';
    }

    protected function bindings(): array
    {
        return [
            ReportServiceInterface::class => ReportService::class,
        ];
    }
}
