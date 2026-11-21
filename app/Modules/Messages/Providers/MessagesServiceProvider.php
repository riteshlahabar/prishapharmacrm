<?php

namespace App\Modules\Messages\Providers;

use App\Core\Providers\ModuleServiceProvider;

class MessagesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Messages';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/messages.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
