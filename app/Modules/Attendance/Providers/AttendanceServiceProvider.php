<?php

namespace App\Modules\Attendance\Providers;

use App\Core\Providers\ModuleServiceProvider;

class AttendanceServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Attendance';
    }

    /**
     * Contract => implementation. Populated as the module is built; see
     * docs/modules/attendance.md for current status.
     */
    protected function bindings(): array
    {
        return [];
    }
}
