<?php

namespace App\Modules\Messages\Http\Resources;

use App\Modules\Messages\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The message shape the MR app decodes.
 *
 * `is_read` is derived from `read_at` — the apps asked for a flag, the table
 * keeps one source of truth.
 *
 * @property Message $resource
 */
class MessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $m = $this->resource;

        return [
            'id' => $m->id,
            'subject' => $m->subject,
            'body' => $m->body,
            'is_read' => $m->isRead(),
            'read_at' => $m->read_at?->toIso8601String(),
            'sent_at' => $m->created_at?->toIso8601String(),
            'from' => $this->whenLoaded('sender', fn () => [
                'id' => $m->sender->id,
                'name' => $m->sender->name,
                'role' => $m->sender->role?->value,
            ]),
            'to' => $this->whenLoaded('recipient', fn () => [
                'id' => $m->recipient->id,
                'name' => $m->recipient->name,
                'role' => $m->recipient->role?->value,
            ]),
            // Context, when the sender attached any.
            'person' => $this->whenLoaded('person', fn () => $m->person === null ? null : [
                'id' => $m->person->id,
                'name' => $m->person->name,
                'type' => $m->person->type,
                'mobile' => $m->person->mobile,
            ]),
            'call_log_id' => $m->call_log_id,
        ];
    }
}
