<?php

namespace App\Modules\Persons\Providers;

use App\Core\Providers\ModuleServiceProvider;

class PersonsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Persons';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/persons.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
