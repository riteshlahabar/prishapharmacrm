<?php

namespace App\Modules\Visits\Models;

use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Database\Factories\VisitFactory;
use App\Modules\Visits\Enums\VisitPurpose;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One call by an MR on a person. State and relationships only.
 */
#[Fillable([
    'mr_id', 'person_id', 'visit_date', 'purpose', 'in_time', 'out_time',
    'latitude', 'longitude', 'location_fetched_at', 'notes',
])]
#[UseFactory(VisitFactory::class)]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    protected $table = 'visits';

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'purpose' => VisitPurpose::class,
            'location_fetched_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function mr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mr_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /** What was detailed on this visit. Empty on most of them. */
    public function products(): HasMany
    {
        return $this->hasMany(VisitProduct::class, 'visit_id');
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Null unless both ends were given — an unfinished entry stays honest. */
    public function durationMinutes(): ?int
    {
        if ($this->in_time === null || $this->out_time === null) {
            return null;
        }

        $in = strtotime($this->in_time);
        $out = strtotime($this->out_time);

        return $out >= $in ? (int) round(($out - $in) / 60) : null;
    }

    public function durationLabel(): string
    {
        $minutes = $this->durationMinutes();

        return $minutes === null
            ? '—'
            : sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * MySQL hands a TIME column back as "10:30:00". The seconds are noise on a
     * report — the app only ever sends H:i.
     */
    public function inTimeLabel(): string
    {
        return $this->clockLabel($this->in_time);
    }

    public function outTimeLabel(): string
    {
        return $this->clockLabel($this->out_time);
    }

    private function clockLabel(?string $time): string
    {
        return $time === null ? '—' : substr($time, 0, 5);
    }

    public function purposeLabel(): string
    {
        return $this->purpose?->label() ?? '—';
    }
}
