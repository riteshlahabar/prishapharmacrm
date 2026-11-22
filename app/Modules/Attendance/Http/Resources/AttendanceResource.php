<?php

namespace App\Modules\Attendance\Http\Resources;

use App\Modules\Attendance\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The attendance shape the MR app decodes. `can_check_in` / `can_check_out`
 * are computed here so the app's two buttons never have to re-derive the rule
 * the server already enforces — and a reinstall cannot guess wrong.
 *
 * @property Attendance $resource
 */
class AttendanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $a = $this->resource;

        return [
            'id' => $a->id,
            'date' => $a->date?->toDateString(),
            'check_in_at' => $a->check_in_at?->toIso8601String(),
            'check_in_lat' => $a->check_in_lat,
            'check_in_lng' => $a->check_in_lng,
            'check_out_at' => $a->check_out_at?->toIso8601String(),
            'check_out_lat' => $a->check_out_lat,
            'check_out_lng' => $a->check_out_lng,
            'worked_minutes' => $a->workedMinutes(),
            'status' => $a->status(),
            'can_check_in' => ! $a->isCheckedIn(),
            'can_check_out' => $a->isCheckedIn() && ! $a->isCheckedOut(),
        ];
    }
}
