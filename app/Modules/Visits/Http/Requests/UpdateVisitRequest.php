<?php

namespace App\Modules\Visits\Http\Requests;

use App\Modules\Visits\Enums\VisitPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Amending a visit already filed from the MR app: the purpose was wrong, the
 * clocks were a guess, the notes grew, or a product line was missed. Every
 * field is optional — the app sends only what it is changing.
 *
 * Three things are deliberately *not* amendable:
 *
 * - `person_id`, as on a call log: a visit was made on someone, and
 *   re-pointing it would rewrite history. Filing it again is the honest fix.
 * - the coordinates and `location_fetched_at`, which are evidence of where
 *   the rep stood when they filed — an editable GPS reading is not evidence.
 * - the photo, for the same reason.
 *
 * Both clocks move together (`required_with`), the way the coordinates do on
 * the store request: that way "out before in" can always be checked against
 * the value in the same payload rather than against the stored row.
 */
class UpdateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purpose' => ['sometimes', 'required', Rule::in(VisitPurpose::values())],
            'in_time' => ['sometimes', 'nullable', 'date_format:H:i', 'required_with:out_time'],
            'out_time' => ['sometimes', 'nullable', 'date_format:H:i', 'after_or_equal:in_time'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Optional on every visit and every call: most are a
            // conversation with nothing to record. A line that is sent is
            // checked here; ProductService::priceLines is the second belt,
            // for a product deleted between the app loading its list and
            // posting the amendment.
            'products' => ['sometimes', 'array', 'max:50'],
            'products.*.product_id' => ['required_with:products', 'integer', Rule::exists('products', 'id')],
            'products.*.box_qty' => ['required_with:products', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'out_time.after_or_equal' => 'The out time cannot be before the in time.',
            'in_time.required_with' => 'Send both times, or neither.',
            'products.*.product_id.exists' => 'One of those products no longer exists.',
            'products.*.box_qty.min' => 'A product line needs at least one box.',
        ];
    }

    /** Only the keys actually sent, so a missing field is left alone. */
    public function payload(): array
    {
        $data = [];

        if ($this->has('purpose')) {
            $data['purpose'] = $this->string('purpose')->value();
        }

        foreach (['in_time', 'out_time'] as $clock) {
            if ($this->has($clock)) {
                $data[$clock] = $this->input($clock) ?: null;
            }
        }

        if ($this->has('notes')) {
            $data['notes'] = $this->filled('notes') ? $this->string('notes')->trim()->value() : null;
        }

        // Absent means "leave the lines alone"; present and empty means
        // "the rep removed them all".
        if ($this->has('products')) {
            $data['products'] = $this->productLines();
        }

        return $data;
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
