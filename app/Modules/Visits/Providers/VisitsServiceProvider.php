<?php

namespace App\Modules\Visits\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Visits\Models\Visit;
use App\Modules\Visits\Policies\VisitPolicy;
use App\Modules\Visits\Repositories\Contracts\VisitRepositoryInterface;
use App\Modules\Visits\Repositories\EloquentVisitRepository;
use App\Modules\Visits\Services\Contracts\VisitPhotoStoreInterface;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use App\Modules\Visits\Services\VisitPhotoStore;
use App\Modules\Visits\Services\VisitService;

class VisitsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Visits';
    }

    /**
     * Contract => implementation; see docs/modules/visits.md for status.
     */
    protected function bindings(): array
    {
        return [
            VisitRepositoryInterface::class => EloquentVisitRepository::class,
            VisitServiceInterface::class => VisitService::class,
            VisitPhotoStoreInterface::class => VisitPhotoStore::class,
        ];
    }

    /** @return array<class-string, class-string> */
    protected function policies(): array
    {
        return [
            Visit::class => VisitPolicy::class,
        ];
    }
}
