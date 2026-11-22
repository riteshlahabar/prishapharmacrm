<?php

namespace App\Modules\Calls\Http\Requests;

use App\Modules\Calls\Enums\CallStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Amending a call already logged: the outcome changed, the notes grew, or the
 * call-back moved. Every field is optional — the app sends only what changed.
 *
 * `person_id` is not amendable: a call was made to someone, and re-pointing it
 * would rewrite history. Logging it again against the right person is the
 * honest fix.
 */
class UpdateCallLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(CallStatus::values())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'follow_up_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'follow_up_at.after' => 'The follow-up date has to be in the future.',
        ];
    }

    /** Only the keys actually sent, so a missing field is left alone. */
    public function payload(): array
    {
        $data = [];

        if ($this->has('status')) {
            $data['status'] = $this->string('status')->value();
        }

        if ($this->has('notes')) {
            $data['notes'] = $this->filled('notes') ? $this->string('notes')->trim()->value() : null;
        }

        if ($this->has('follow_up_at')) {
            $data['follow_up_at'] = $this->input('follow_up_at') ?: null;
        }

        return $data;
    }

    protected function prepareForValidation(): void
    {
        // A status that does not take a call-back makes the date moot; the
        // service clears it, so a date sent alongside is simply ignored.
        if ($this->input('status') !== null && $this->input('status') !== CallStatus::Callback->value) {
            $this->request->remove('follow_up_at');
        }
    }
}
