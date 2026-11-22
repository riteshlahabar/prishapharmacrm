<?php

namespace App\Modules\Messages\Http\Requests;

use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A message sent from the telecaller app.
 *
 * `from_user_id` is deliberately absent — the service takes it from the
 * authenticated caller — and so is `read_at`: a sender who could set it would
 * be marking their own message read.
 *
 * The recipient is checked against the same `recipientsFor` list the picker
 * shows, so the form and the rule cannot drift apart.
 */
class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'integer', $this->recipientRule()],
            // Optional context. The person must be one the sender can see;
            // the call must be one the sender actually made.
            'person_id' => [
                'nullable',
                'integer',
                Rule::exists('persons', 'id')->whereIn('region_id', $this->senderRegionIds()),
            ],
            'call_log_id' => [
                'nullable',
                'integer',
                Rule::exists('call_logs', 'id')->where('telecaller_id', $this->user()?->id),
            ],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_user_id.*' => 'That medical rep is not one you can write to.',
            'person_id.exists' => 'That person is not in your regions.',
            'call_log_id.exists' => 'That call is not one of yours.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'to_user_id' => (int) $this->input('to_user_id'),
            'person_id' => $this->input('person_id') !== null ? (int) $this->input('person_id') : null,
            'call_log_id' => $this->input('call_log_id') !== null ? (int) $this->input('call_log_id') : null,
            'subject' => $this->string('subject')->trim()->value(),
            'body' => $this->string('body')->trim()->value(),
        ];
    }

    /**
     * Asks the module's own rule rather than re-stating it in a where clause:
     * "an MR in my regions, or the one assigned to the person I am writing
     * about".
     */
    private function recipientRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $sender = $this->user();

            if ($sender === null
                || ! app(MessageServiceInterface::class)->canSendTo($sender, (int) $value, $this->input('person_id'))) {
                $fail('That medical rep is not one you can write to.');
            }
        };
    }

    /** @return array<int, int> */
    private function senderRegionIds(): array
    {
        $user = $this->user();

        return $user === null ? [] : app(RegionAssignmentServiceInterface::class)->idsFor($user);
    }
}
