<?php

namespace App\Providers;

use App\Core\Support\AssetVersion;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel renders Tailwind pagination out of the box, which lands in
        // a Bootstrap panel as an unstyled column of links under the table.
        // Cuba is Bootstrap 5, so the matching view is the one to use; the
        // colours come from assets/css/prisha.css.
        Paginator::useBootstrapFive();
        Paginator::defaultView('partials.pagination-links');

        // @assetv('assets/css/prisha.css') — asset() plus the file's mtime,
        // for the stylesheets we edit. Without it a browser holds the cached
        // copy and a CSS change appears not to have landed (AssetVersion).
        Blade::directive(
            'assetv',
            fn (string $expression): string => "<?php echo e(\App\Core\Support\AssetVersion::url({$expression})); ?>"
        );
    }
}
