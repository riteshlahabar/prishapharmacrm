<?php

namespace App\Modules\Messages\Http\Resources;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An MR as the telecaller app's recipient picker needs them: enough to choose
 * by, and nothing else. Deliberately not the full user shape — a picker has
 * no business carrying email addresses or activity flags.
 *
 * @property User $resource
 */
class RecipientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $u = $this->resource;

        return [
            'id' => $u->id,
            'name' => $u->name,
            'mobile' => $u->mobile,
            'region' => $u->region === null ? null : [
                'id' => $u->region->id,
                'name' => $u->region->name,
                'code' => $u->region->code,
            ],
        ];
    }
}
