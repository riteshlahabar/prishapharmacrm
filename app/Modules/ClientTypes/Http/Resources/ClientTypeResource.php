<?php

namespace App\Modules\ClientTypes\Http\Resources;

use App\Modules\ClientTypes\Models\ClientType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One client type as the two Add Client forms need it.
 *
 * `slug` is the value that goes back in `persons.type`, so it — not the id —
 * is what a form posts. `has_specialization` drives the form itself: the
 * field is shown only for the types that carry one, matching what
 * `PersonService` will keep after the write.
 *
 * @property ClientType $resource
 */
class ClientTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $t = $this->resource;

        return [
            'id' => $t->id,
            'slug' => $t->slug,
            'label' => $t->label,
            'colour' => $t->colour,
            'has_specialization' => $t->has_specialization,
        ];
    }
}
