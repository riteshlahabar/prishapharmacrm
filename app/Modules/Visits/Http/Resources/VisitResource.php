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
            'has_photo' => $v->hasPhoto(),
            // Where to fetch it, not the bytes: a list of twenty visits must
            // not carry twenty photos nobody has opened yet. The file stays
            // on the private disk — this URL is the token-authenticated
            // `api.v1.visits.photo` route, inheriting the visit's own scope,
            // so it is worth nothing to anyone without a bearer. Null when
            // there is no photo, so a client never requests a certain 404.
            'photo_url' => $v->hasPhoto()
                ? route('api.v1.visits.photo', $v->id)
                : null,
            'photo_taken_at' => $v->photo_taken_at?->toIso8601String(),
            'notes' => $v->notes,
            // When the server received it, so a late entry is visible as one.
            'recorded_at' => $v->created_at?->toIso8601String(),
            // The lines, when they were loaded. `whenLoaded` so a list
            // endpoint that did not eager load them does not fire a query
            // per row.
            'products' => $this->whenLoaded('products', fn () => $v->products->map(fn ($line) => [
                'product_id' => $line->product_id,
                'name' => $line->productName(),
                'box_qty' => $line->box_qty,
                'unit_cost' => (string) $line->unit_cost,
                'line_total' => number_format($line->lineTotal(), 2, '.', ''),
            ])->values()),
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $v->person->id,
                'name' => $v->person->name,
                'type' => $v->person->type,
                // The master's own wording, as `PersonResource` and the panel
                // both give it — so a kind renamed in the panel reads the
                // same on a visit as it does on the client.
                'type_label' => $v->person->typeLabel(),
                'mobile' => $v->person->mobile,
                'city' => $v->person->city,
            ]),
        ];
    }
}
