<?php

namespace App\Modules\Messages\Models;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Database\Factories\MessageFactory;
use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message from a telecaller to an MR. State and relationships only.
 *
 * Read state is `read_at` alone; `isRead()` derives the flag so there is no
 * second column to fall out of step with it.
 */
#[Fillable([
    'from_user_id', 'to_user_id', 'person_id', 'call_log_id',
    'subject', 'body', 'read_at',
])]
#[UseFactory(MessageFactory::class)]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $table = 'messages';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /** Optional context: the person the message is about. */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /** Optional context: the call that prompted it. */
    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function readLabel(): string
    {
        return $this->read_at === null
            ? 'Unread'
            : 'Read '.$this->read_at->format('d M Y, g:i A');
    }
}
