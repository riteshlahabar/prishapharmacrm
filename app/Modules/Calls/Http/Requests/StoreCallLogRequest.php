<?php

namespace App\Modules\Calls\Http\Requests;

use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
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
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.exists' => 'That person is not on your active list.',
            'follow_up_at.required' => 'A call-back needs a date to come back on.',
            'follow_up_at.prohibited' => 'Only a call-back carries a follow-up date.',
            'follow_up_at.after' => 'The follow-up date has to be in the future.',
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
            'follow_up_at' => $this->input('follow_up_at') ?: null,
        ];
    }
}
