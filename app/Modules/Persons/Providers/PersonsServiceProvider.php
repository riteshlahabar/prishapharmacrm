<?php

namespace App\Modules\Persons\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Persons\Repositories\Contracts\PersonRepositoryInterface;
use App\Modules\Persons\Repositories\EloquentPersonRepository;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Persons\Services\PersonService;

class PersonsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Persons';
    }

    /**
     * Contract => implementation; see docs/modules/persons.md for status.
     */
    protected function bindings(): array
    {
        return [
            PersonRepositoryInterface::class => EloquentPersonRepository::class,
            PersonServiceInterface::class => PersonService::class,
        ];
    }
}
