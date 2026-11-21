<?php

namespace App\Modules\Regions\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Regions\Repositories\Contracts\RegionRepositoryInterface;
use App\Modules\Regions\Repositories\EloquentRegionRepository;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Regions\Services\RegionAssignmentService;
use App\Modules\Regions\Services\RegionService;

class RegionsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Regions';
    }

    /**
     * Contract => implementation; see docs/modules/regions.md for status.
     */
    protected function bindings(): array
    {
        return [
            RegionRepositoryInterface::class => EloquentRegionRepository::class,
            RegionServiceInterface::class => RegionService::class,
            RegionAssignmentServiceInterface::class => RegionAssignmentService::class,
        ];
    }
}
