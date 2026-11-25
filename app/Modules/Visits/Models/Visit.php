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
    'latitude', 'longitude', 'location_fetched_at', 'photo_path',
    'photo_taken_at', 'notes',
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
            'photo_taken_at' => 'datetime',
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

    /**
     * Whether a shopfront photo was filed with this visit. The path alone is
     * the answer: the file is only ever written after a successful upload,
     * and a missing file on disk is a deploy problem, not a different visit.
     */
    public function hasPhoto(): bool
    {
        return $this->photo_path !== null && $this->photo_path !== '';
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * The pin on an external map, for the "open in Google Maps" link.
     */
    public function mapsUrl(): ?string
    {
        return $this->hasLocation()
            ? 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude
            : null;
    }

    /**
     * An OpenStreetMap frame centred on the visit, for the panel's detail
     * page. OSM rather than Google because it embeds with **no API key and
     * no billing account** — a Google frame would put a key in the page's
     * HTML. Swapping it is one method (docs/modules/visits.md).
     *
     * `$span` is the box around the pin in degrees; 0.004 is roughly 400 m,
     * close enough to read the street but wide enough to place it.
     */
    public function mapEmbedUrl(float $span = 0.004): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        return 'https://www.openstreetmap.org/export/embed.html?'.http_build_query([
            // minLon, minLat, maxLon, maxLat — OSM's order, not lat-first.
            'bbox' => implode(',', [$lng - $span, $lat - $span, $lng + $span, $lat + $span]),
            'layer' => 'mapnik',
            'marker' => $lat.','.$lng,
        ]);
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
     * report — the app only ever sends H:i. The panel is on a 12-hour clock
     * throughout, so "10:30:00" reads as "10:30 AM".
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
        return $time === null
            ? '—'
            : date('g:i A', strtotime(substr($time, 0, 5)));
    }

    public function purposeLabel(): string
    {
        return $this->purpose?->label() ?? '—';
    }
}
