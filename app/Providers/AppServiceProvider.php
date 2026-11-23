<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
