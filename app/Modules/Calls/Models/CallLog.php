<?php

namespace App\Modules\Calls\Models;

use App\Modules\Calls\Database\Factories\CallLogFactory;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One call by a telecaller on a person. State and relationships only.
 */
#[Fillable([
    'telecaller_id', 'person_id', 'called_at', 'status', 'notes',
    'follow_up_at', 'follow_up_done_at',
])]
#[UseFactory(CallLogFactory::class)]
class CallLog extends Model
{
    /** @use HasFactory<CallLogFactory> */
    use HasFactory;

    protected $table = 'call_logs';

    protected function casts(): array
    {
        return [
            'status' => CallStatus::class,
            'called_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'follow_up_done_at' => 'datetime',
        ];
    }

    public function telecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'telecaller_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /** What was talked through on this call. Empty on most of them. */
    public function products(): HasMany
    {
        return $this->hasMany(CallLogProduct::class, 'call_log_id');
    }

    /** A follow-up still waiting to be made. */
    public function hasOpenFollowUp(): bool
    {
        return $this->follow_up_at !== null && $this->follow_up_done_at === null;
    }

    /** Open, and the date has arrived. This is what the queue shows. */
    public function isFollowUpDue(): bool
    {
        return $this->hasOpenFollowUp() && ! $this->follow_up_at->isFuture();
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '—';
    }

    public function statusBadge(): string
    {
        return $this->status?->badge() ?? 'secondary';
    }

    public function followUpLabel(): string
    {
        if ($this->follow_up_at === null) {
            return '—';
        }

        return $this->follow_up_at->format('d M Y, H:i')
            .($this->follow_up_done_at !== null ? ' (done)' : '');
    }
}
