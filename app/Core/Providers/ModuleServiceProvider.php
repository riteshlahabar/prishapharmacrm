<?php

namespace App\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base provider for a module in the modular monolith. A module owns its
 * bindings, routes, views and migrations; the application only knows the list
 * in config/modules.php.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Module directory name under app/Modules, e.g. "Persons". */
    abstract protected function name(): string;

    /** Contract => implementation bindings for this module. */
    protected function bindings(): array
    {
        return [];
    }

    public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    public function boot(): void
    {
        $path = $this->modulePath();

        if (is_dir("{$path}/Database/Migrations")) {
            $this->loadMigrationsFrom("{$path}/Database/Migrations");
        }

        if (is_dir("{$path}/Resources/Views")) {
            $this->loadViewsFrom("{$path}/Resources/Views", strtolower($this->name()));
        }

        $this->loadRoutes($path);
    }

    protected function loadRoutes(string $path): void
    {
        if (is_file($web = "{$path}/Routes/web.php")) {
            Route::middleware('web')->group($web);
        }

        if (is_file($api = "{$path}/Routes/api.php")) {
            Route::middleware('api')->prefix('api/v1')->group($api);
        }
    }

    protected function modulePath(): string
    {
        return app_path("Modules/{$this->name()}");
    }
}
