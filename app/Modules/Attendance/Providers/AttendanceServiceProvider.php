<?php

namespace App\Modules\Attendance\Providers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Modules\Attendance\Repositories\EloquentAttendanceRepository;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;

class AttendanceServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Attendance';
    }

    /**
     * Contract => implementation; see docs/modules/attendance.md for status.
     */
    protected function bindings(): array
    {
        return [
            AttendanceRepositoryInterface::class => EloquentAttendanceRepository::class,
            AttendanceServiceInterface::class => AttendanceService::class,
        ];
    }
}
