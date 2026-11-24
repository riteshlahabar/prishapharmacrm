<?php

namespace App\Modules\Users\Http\Resources;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A counterpart one app may hand a client to: an MR in the telecaller app's
 * picker, a telecaller in the MR app's.
 *
 * Deliberately not the full user shape — a picker has no business carrying
 * email addresses or activity flags (same reasoning as
 * `Messages\Http\Resources\RecipientResource`, which answers a different
 * question: who may be *written* to).
 *
 * `regions` is the flattened `covered_regions` accessor, so one shape serves
 * both roles — an MR's single territory and a telecaller's several. The app
 * joins the names into the row's subtitle and never branches on role.
 *
 * @property User $resource
 */
class AssigneeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $u = $this->resource;

        return [
            'id' => $u->id,
            'name' => $u->name,
            'mobile' => $u->mobile,
            'regions' => $u->covered_regions
                ->map(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'code' => $region->code,
                ])
                ->values()
                ->all(),
        ];
    }
}
