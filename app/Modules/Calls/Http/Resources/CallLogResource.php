<?php

namespace App\Modules\Calls\Http\Resources;

use App\Modules\Calls\Models\CallLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The call shape the telecaller app decodes.
 *
 * `follow_up_due` is computed here so the app's queue badge never has to
 * compare dates against a device clock that may be wrong.
 *
 * @property CallLog $resource
 */
class CallLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $c = $this->resource;

        return [
            'id' => $c->id,
            'status' => $c->status?->value,
            'status_label' => $c->statusLabel(),
            'notes' => $c->notes,
            'called_at' => $c->called_at?->toIso8601String(),
            'follow_up_at' => $c->follow_up_at?->toIso8601String(),
            'follow_up_done_at' => $c->follow_up_done_at?->toIso8601String(),
            'follow_up_open' => $c->hasOpenFollowUp(),
            'follow_up_due' => $c->isFollowUpDue(),
            // The lines, when they were loaded. `whenLoaded` so a list
            // endpoint that did not eager load them does not fire a query
            // per row.
            'products' => $this->whenLoaded('products', fn () => $c->products->map(fn ($line) => [
                'product_id' => $line->product_id,
                'name' => $line->productName(),
                'box_qty' => $line->box_qty,
                'unit_cost' => (string) $line->unit_cost,
                'line_total' => number_format($line->lineTotal(), 2, '.', ''),
            ])->values()),
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $c->person->id,
                'name' => $c->person->name,
                'type' => $c->person->type,
                'mobile' => $c->person->mobile,
                'city' => $c->person->city,
            ]),
        ];
    }
}
