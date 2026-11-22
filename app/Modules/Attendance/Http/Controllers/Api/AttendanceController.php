<?php

namespace App\Modules\Attendance\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceConflict;
use App\Modules\Attendance\Http\Requests\GeoPunchRequest;
use App\Modules\Attendance\Http\Resources\AttendanceResource;
use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/attendance/*` — the MR app's home screen.
 *
 * The app never sends a time. It posts coordinates, the server stamps the
 * row, and `today` hands back the button state so a reinstall cannot double
 * check-in (docs/api/v1-mr.md).
 */
class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceServiceInterface $attendance) {}

    public function checkIn(GeoPunchRequest $request): JsonResponse
    {
        try {
            $row = $this->attendance->checkIn($request->user(), $request->latitude(), $request->longitude());
        } catch (AttendanceConflict $e) {
            return $this->conflict($e);
        }

        return ApiResponse::success(new AttendanceResource($row), 'Checked in.', 201);
    }

    public function checkOut(GeoPunchRequest $request): JsonResponse
    {
        try {
            $row = $this->attendance->checkOut($request->user(), $request->latitude(), $request->longitude());
        } catch (AttendanceConflict $e) {
            return $this->conflict($e);
        }

        return ApiResponse::success(new AttendanceResource($row), 'Checked out.');
    }

    /**
     * Today's state. A day with no row yet is not an error — the app needs a
     * usable answer to render its buttons, so this returns the same shape
     * with both flags set for a fresh day.
     */
    public function today(Request $request): JsonResponse
    {
        $row = $this->attendance->today($request->user());

        if ($row === null) {
            return ApiResponse::success([
                'id' => null,
                'date' => now()->toDateString(),
                'check_in_at' => null,
                'check_out_at' => null,
                'worked_minutes' => null,
                'status' => 'Absent',
                'can_check_in' => true,
                'can_check_out' => false,
            ], 'Not checked in yet.');
        }

        return ApiResponse::success(new AttendanceResource($row), 'Today loaded.');
    }

    public function history(Request $request): JsonResponse
    {
        $page = $this->attendance->historyFor($request->user(), [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ], (int) $request->integer('per_page', 20));

        return ApiResponse::paginated(
            $page->through(fn ($row) => (new AttendanceResource($row))->resolve()),
            'Attendance history loaded.',
        );
    }

    /** 409: the request was well-formed, the day's state just forbids it. */
    private function conflict(AttendanceConflict $e): JsonResponse
    {
        return ApiResponse::error($e->getMessage(), ['reason' => $e->reason], 409);
    }
}
