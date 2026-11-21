<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers every module listed in config/modules.php. Adding a module means
 * adding one line to that config -- nothing else in the application changes.
 */
class ModuleRegistrarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (config('modules.providers', []) as $provider) {
            $this->app->register($provider);
        }
    }
}
