<?php

use App\Modules\Access\Providers\AccessServiceProvider;
use App\Modules\Attendance\Providers\AttendanceServiceProvider;
use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Calls\Providers\CallsServiceProvider;
use App\Modules\ClientTypes\Providers\ClientTypesServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\Messages\Providers\MessagesServiceProvider;
use App\Modules\Persons\Providers\PersonsServiceProvider;
use App\Modules\Products\Providers\ProductsServiceProvider;
use App\Modules\Regions\Providers\RegionsServiceProvider;
use App\Modules\Reports\Providers\ReportsServiceProvider;
use App\Modules\Users\Providers\UsersServiceProvider;
use App\Modules\Visits\Providers\VisitsServiceProvider;

/*
|--------------------------------------------------------------------------
| Modular monolith registry
|--------------------------------------------------------------------------
| Each entry is a module service provider under app/Modules/<Name>/Providers.
| Order matters only where one module's bindings depend on another's.
| Documented per module in docs/modules/*.md (see docs/index.md).
*/

return [
    'providers' => [
        AccessServiceProvider::class,
        AuthServiceProvider::class,
        UsersServiceProvider::class,
        RegionsServiceProvider::class,
        // Before Persons: the client form reads its options from this master.
        ClientTypesServiceProvider::class,
        PersonsServiceProvider::class,
        // Before Visits and Calls: both price a line from its contract.
        ProductsServiceProvider::class,
        AttendanceServiceProvider::class,
        VisitsServiceProvider::class,
        CallsServiceProvider::class,
        MessagesServiceProvider::class,
        DashboardServiceProvider::class,
        // Last: it reads every other module's contracts and owns no table.
        ReportsServiceProvider::class,
    ],
];
