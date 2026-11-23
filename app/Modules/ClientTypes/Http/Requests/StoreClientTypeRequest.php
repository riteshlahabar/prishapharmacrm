<?php

namespace App\Modules\ClientTypes\Http\Requests;

use App\Modules\ClientTypes\Models\ClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create / edit a client type. One class for both, with one asymmetry: the
 * slug is required and unique on create and absent on update, because every
 * client row stores it (see the create migration).
 *
 * Authorization is the route's job (`permission:` middleware).
 */
class StoreClientTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function creating(): bool
    {
        return $this->route('client_type') === null;
    }

    /**
     * Slugify before validating, not only in `payload()`: the column stores
     * the slugified form, so validating the raw input would let "Medical
     * Store" pass `unique` and then collide with an existing `medical-store`.
     * An empty slug field means "derive it from the label", which is what
     * most admins will do.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->creating()) {
            return;
        }

        $source = $this->filled('slug') ? $this->input('slug') : $this->input('label', '');

        $this->merge(['slug' => Str::of((string) $source)->slug()->limit(40, '')->value()]);
    }

    public function rules(): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:80'],
            'colour' => ['required', Rule::in(ClientType::COLOURS)],
            'has_specialization' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];

        if ($this->creating()) {
            $rules['slug'] = [
                'required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('client_types', 'slug'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The code may contain lower-case letters, digits and hyphens only.',
            'slug.unique' => 'A client type with that code already exists.',
            'slug.required' => 'Give the type a name the code can be built from.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $payload = [
            'label' => $this->string('label')->trim()->value(),
            'colour' => $this->string('colour')->value(),
            'has_specialization' => $this->boolean('has_specialization'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) ($this->input('sort_order') ?: 0),
        ];

        if ($this->creating()) {
            $payload['slug'] = $this->string('slug')->value();
        }

        return $payload;
    }
}
