<?php

namespace App\Modules\Visits\Http\Resources;

use App\Modules\Visits\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The visit shape both the MR app and (from Phase 6) reports decode.
 *
 * @property Visit $resource
 */
class VisitResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $v = $this->resource;

        return [
            'id' => $v->id,
            'visit_date' => $v->visit_date?->toDateString(),
            'purpose' => $v->purpose?->value,
            'purpose_label' => $v->purposeLabel(),
            'in_time' => $v->in_time,
            'out_time' => $v->out_time,
            'duration_minutes' => $v->durationMinutes(),
            'latitude' => $v->latitude,
            'longitude' => $v->longitude,
            'location_fetched_at' => $v->location_fetched_at?->toIso8601String(),
            'notes' => $v->notes,
            // When the server received it, so a late entry is visible as one.
            'recorded_at' => $v->created_at?->toIso8601String(),
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $v->person->id,
                'name' => $v->person->name,
                'type' => $v->person->type,
                'mobile' => $v->person->mobile,
                'city' => $v->person->city,
            ]),
        ];
    }
}
