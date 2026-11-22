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
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $c->person->id,
                'name' => $c->person->name,
                'type' => $c->person->type?->value,
                'mobile' => $c->person->mobile,
                'city' => $c->person->city,
            ]),
        ];
    }
}
