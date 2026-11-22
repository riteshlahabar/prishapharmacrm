<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An MR's presence for one day. State and relationships only.
 */
#[Fillable([
    'mr_id', 'date',
    'check_in_at', 'check_in_lat', 'check_in_lng',
    'check_out_at', 'check_out_lat', 'check_out_lng',
])]
class Attendance extends Model
{
    protected $table = 'attendances';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            // Strings, not floats: these are exact decimals and rounding a
            // coordinate moves it on the map.
            'check_in_lat' => 'decimal:7',
            'check_in_lng' => 'decimal:7',
            'check_out_lat' => 'decimal:7',
            'check_out_lng' => 'decimal:7',
        ];
    }

    public function mr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mr_id');
    }

    public function isCheckedIn(): bool
    {
        return $this->check_in_at !== null;
    }

    public function isCheckedOut(): bool
    {
        return $this->check_out_at !== null;
    }

    /** Null until both ends exist; the panel shows a dash rather than a guess. */
    public function workedMinutes(): ?int
    {
        if (! $this->isCheckedIn() || ! $this->isCheckedOut()) {
            return null;
        }

        return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
    }

    public function workedLabel(): string
    {
        $minutes = $this->workedMinutes();

        return $minutes === null
            ? '—'
            : sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }

    public function status(): string
    {
        return match (true) {
            $this->isCheckedOut() => 'Completed',
            $this->isCheckedIn() => 'On duty',
            default => 'Absent',
        };
    }
}
