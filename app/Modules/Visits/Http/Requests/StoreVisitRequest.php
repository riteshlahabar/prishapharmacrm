<?php

namespace App\Modules\Visits\Http\Requests;

use App\Modules\Visits\Enums\VisitPurpose;
use Illuminate\Foundation\Http\FormRequest;
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'out_time.after_or_equal' => 'The out time cannot be before the in time.',
            'person_id.exists' => 'That person is not on the active list.',
            'visit_date.before_or_equal' => 'A visit cannot be dated in the future.',
            'latitude.required_with' => 'Both coordinates are needed, or neither.',
            'longitude.required_with' => 'Both coordinates are needed, or neither.',
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
            'location_fetched_at' => $this->input('location_fetched_at') ?: null,
            'notes' => $this->filled('notes') ? $this->string('notes')->trim()->value() : null,
        ];
    }
}
