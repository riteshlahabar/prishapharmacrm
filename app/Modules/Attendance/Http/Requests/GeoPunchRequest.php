<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The body of a check-in or check-out: coordinates, and nothing else.
 *
 * There is deliberately no timestamp field. The server stamps the row, so a
 * device with a wound-back clock cannot forge a shift — the one rule the
 * whole attendance feature rests on.
 */
class GeoPunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required_with' => 'Both coordinates are needed, or neither.',
            'longitude.required_with' => 'Both coordinates are needed, or neither.',
        ];
    }

    public function latitude(): ?float
    {
        return $this->input('latitude') !== null ? (float) $this->input('latitude') : null;
    }

    public function longitude(): ?float
    {
        return $this->input('longitude') !== null ? (float) $this->input('longitude') : null;
    }
}
