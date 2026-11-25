<?php

namespace App\Modules\Calls\Http\Requests;

use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A call filed from the telecaller app.
 *
 * `telecaller_id` is deliberately absent: the service takes it from the
 * authenticated caller. `called_at` is absent for the same reason — the
 * server stamps it, so a device cannot back-date work.
 *
 * The person must be **in one of the caller's regions**, not merely exist:
 * the app only ever shows a scoped queue, so anything else is a tampered
 * payload (docs/modules/calls.md).
 */
class StoreCallLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => [
                'required',
                'integer',
                Rule::exists('persons', 'id')
                    ->where('is_active', true)
                    ->whereIn('region_id', $this->callerRegionIds()),
            ],
            'status' => ['required', Rule::in(CallStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
            // A call-back has to be in the future to mean anything, and the
            // status has to be the one that asks for it.
            'follow_up_at' => [
                'nullable',
                'date',
                'after:now',
                Rule::requiredIf(fn () => $this->input('status') === CallStatus::Callback->value),
                Rule::prohibitedIf(fn () => $this->input('status') !== CallStatus::Callback->value),
            ],
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
            'person_id.exists' => 'That person is not on your active list.',
            'follow_up_at.required' => 'A call-back needs a date to come back on.',
            'follow_up_at.prohibited' => 'Only a call-back carries a follow-up date.',
            'follow_up_at.after' => 'The follow-up date has to be in the future.',
            'products.*.product_id.exists' => 'One of those products no longer exists.',
            'products.*.box_qty.min' => 'A product line needs at least one box.',
        ];
    }

    /**
     * The regions the signed-in telecaller covers. An empty list makes the
     * `exists` rule match nothing, which is the right answer for someone who
     * has been given no territory yet.
     *
     * @return array<int, int>
     */
    private function callerRegionIds(): array
    {
        $user = $this->user();

        return $user === null
            ? []
            : app(RegionAssignmentServiceInterface::class)->idsFor($user);
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'person_id' => (int) $this->input('person_id'),
            'status' => $this->string('status')->value(),
            'notes' => $this->filled('notes') ? $this->string('notes')->trim()->value() : null,
            'follow_up_at' => $this->followUpAt(),
            'products' => $this->productLines(),
        ];
    }

    /**
     * The promised call-back, moved into the app timezone before Eloquent
     * writes it. The app sends an instant with its offset (`toUtc()`);
     * Eloquent formats a datetime in whatever zone the value carries but
     * reads it back in the app's, so a `Z` value stored raw would come back
     * 5h30 out — see the IST note in `config/app.php` and the same shift in
     * `StoreVisitRequest::instant()`.
     */
    private function followUpAt(): ?Carbon
    {
        $value = $this->input('follow_up_at');

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
