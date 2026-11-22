<?php

namespace Tests\Feature\Users;

use App\Modules\Persons\Models\Person;
use Tests\TestCase;

/**
 * A field that is normalised on the way into the database must be normalised
 * *before* it is validated, or `unique` checks a different string than the one
 * that gets stored — which is a 500 from the index rather than a field error.
 */
class NormalisationTest extends TestCase
{
    public function test_a_case_variant_duplicate_email_is_a_field_error(): void
    {
        $this->mr(null, ['email' => 'taken@example.com', 'mobile' => '9700000001']);

        $this->actingAs($this->admin())
            ->from('/mrs/create')
            ->post('/mrs', [
                'name' => 'Clash',
                'email' => 'TAKEN@Example.COM',
                'mobile' => '9700000002',
                'password' => 'Password#123',
                'password_confirmation' => 'Password#123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_a_padded_duplicate_mobile_is_a_field_error(): void
    {
        $this->mr(null, ['email' => 'pad@example.com', 'mobile' => '9700000003']);

        $this->actingAs($this->admin())
            ->from('/mrs/create')
            ->post('/mrs', [
                'name' => 'Padded',
                'email' => 'other@example.com',
                'mobile' => '  9700000003  ',
                'password' => 'Password#123',
                'password_confirmation' => 'Password#123',
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_a_padded_duplicate_person_mobile_is_a_field_error(): void
    {
        Person::factory()->create(['mobile' => '8700000001']);

        $this->actingAs($this->admin())
            ->from('/persons/create')
            ->post('/persons', [
                'name' => 'Padded Person',
                'type' => 'doctor',
                'mobile' => ' 8700000001 ',
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_a_case_variant_duplicate_email_on_edit_is_a_field_error(): void
    {
        $this->mr(null, ['email' => 'first@example.com', 'mobile' => '9700000004']);
        $second = $this->mr(null, ['email' => 'second@example.com', 'mobile' => '9700000005']);

        $this->actingAs($this->admin())
            ->from("/mrs/{$second->id}/edit")
            ->put("/mrs/{$second->id}", [
                'name' => $second->name,
                'email' => 'FIRST@Example.com',
                'mobile' => '9700000005',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('email');
    }
}
