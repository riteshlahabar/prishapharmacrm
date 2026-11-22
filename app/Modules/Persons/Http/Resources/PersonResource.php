<?php

namespace App\Modules\Persons\Http\Resources;

use App\Modules\Persons\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The person shape both Flutter apps decode.
 *
 * `last_call` is filled by the telecaller's queue and left out everywhere
 * else. It arrives already rendered by the Calls module's own resource, so a
 * call looks the same here as it does on `/call-logs` — and this module never
 * reaches into `call_logs` itself (docs/conventions.md).
 *
 * @property Person $resource
 */
class PersonResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    public ?array $lastCall = null;

    /** @param  array<string, mixed>|null  $call */
    public function withLastCall(?array $call): static
    {
        $this->lastCall = $call;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $p = $this->resource;

        $data = [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $p->type?->value,
            'type_label' => $p->typeLabel(),
            'mobile' => $p->mobile,
            'email' => $p->email,
            'address' => $p->address,
            'city' => $p->city,
            'specialization' => $p->specialization,
            'is_active' => $p->is_active,
            'region' => $this->whenLoaded('region', fn () => [
                'id' => $p->region->id,
                'name' => $p->region->name,
                'code' => $p->region->code,
            ]),
        ];

        if ($this->lastCall !== null) {
            $data['last_call'] = $this->lastCall;
        }

        return $data;
    }
}
