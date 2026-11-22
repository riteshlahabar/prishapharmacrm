<?php

namespace App\Modules\Messages\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Messages\Repositories\Contracts\MessageRepositoryInterface;
use App\Modules\Messages\Repositories\EloquentMessageRepository;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Messages\Services\MessageService;

class MessagesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Messages';
    }

    /**
     * Contract => implementation; see docs/modules/messages.md for status.
     */
    protected function bindings(): array
    {
        return [
            MessageRepositoryInterface::class => EloquentMessageRepository::class,
            MessageServiceInterface::class => MessageService::class,
        ];
    }
}
