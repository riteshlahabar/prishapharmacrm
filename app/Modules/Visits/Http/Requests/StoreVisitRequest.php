<?php

namespace App\Modules\Visits\Http\Requests;

use App\Modules\Visits\Enums\VisitPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * A visit filed from the MR app.
 *
 * `mr_id` is deliberately absent: the service takes it from the authenticated
 * caller. Accepting it here would let a tampered build file a visit under
 * another rep's name.
 */
class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', Rule::exists('persons', 'id')->where('is_active', true)],
            'purpose' => ['required', Rule::in(VisitPurpose::values())],
            // No future-dating: a visit is reported, not scheduled.
            'visit_date' => ['nullable', 'date', 'before_or_equal:today'],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i', 'after_or_equal:in_time'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_fetched_at' => ['nullable', 'date', 'before_or_equal:now'],
            // The shopfront photo. Optional — a visit filed on a bad signal,
            // or from an older app build, has none. `image` is the mime
            // check, and the extension list keeps out the formats the panel
            // cannot render in an <img>. 8 MB covers a modern phone camera
            // at the quality the app compresses to.
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'photo_taken_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Optional on every visit and every call: most are a
            // conversation with nothing to record. A line that is sent is
            // checked here; ProductService::priceLines is the second belt,
            // for a product deleted between the app loading its list and
            // posting the entry.
            'products' => ['sometimes', 'array', 'max:50'],
            'products.*.product_id' => ['required_with:products', 'integer', Rule::exists('products', 'id')],
            'products.*.box_qty' => ['required_with:products', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'out_time.after_or_equal' => 'The out time cannot be before the in time.',
            'person_id.exists' => 'That person is not on the active list.',
            'visit_date.before_or_equal' => 'A visit cannot be dated in the future.',
            'photo.image' => 'The photo must be an image.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WEBP.',
            'photo.max' => 'That photo is too large — 8 MB is the limit.',
            'latitude.required_with' => 'Both coordinates are needed, or neither.',
            'longitude.required_with' => 'Both coordinates are needed, or neither.',
            'products.*.product_id.exists' => 'One of those products no longer exists.',
            'products.*.box_qty.min' => 'A product line needs at least one box.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'person_id' => (int) $this->input('person_id'),
            'purpose' => $this->string('purpose')->value(),
            'visit_date' => $this->input('visit_date') ?: null,
            'in_time' => $this->input('in_time') ?: null,
            'out_time' => $this->input('out_time') ?: null,
            'latitude' => $this->input('latitude') !== null ? (float) $this->input('latitude') : null,
            'longitude' => $this->input('longitude') !== null ? (float) $this->input('longitude') : null,
            'location_fetched_at' => $this->instant('location_fetched_at'),
            // The file itself, not a path: the service hands it to the photo
            // store, which decides where it lands.
            'photo' => $this->file('photo'),
            'photo_taken_at' => $this->instant('photo_taken_at'),
            'notes' => $this->filled('notes') ? $this->string('notes')->trim()->value() : null,
            'products' => $this->productLines(),
        ];
    }

    /**
     * A device clock the apps send as UTC (`toUtc().toIso8601String()`),
     * moved into the app timezone before Eloquent writes it. Eloquent
     * formats a datetime in whatever zone the value carries but reads it
     * back in the app's, so a `Z` value stored raw would come back 5h30
     * out — see the IST note in `config/app.php`.
     */
    private function instant(string $key): ?Carbon
    {
        $value = $this->input($key);

        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone')) : null;
    }

    /**
     * The product lines, as the apps post them. Shape only — pricing and
     * de-duplication are the Products module's job.
     *
     * @return array<int, array{product_id: int, box_qty: int}>
     */
    private function productLines(): array
    {
        return collect($this->input('products', []))
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'box_qty' => (int) ($row['box_qty'] ?? 0),
            ])
            ->values()
            ->all();
    }
}
