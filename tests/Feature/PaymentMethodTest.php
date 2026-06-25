<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use CreatesUsers;

    public function test_payment_methods_require_authentication(): void
    {
        $this->getJson('/api/payment-methods')->assertUnauthorized();
    }

    public function test_index_returns_empty_list_without_stripe_customer(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/payment-methods')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_store_validates_payment_method(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/payment-methods', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_set_default_returns_not_found_without_stripe_customer(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/payment-methods/pm_missing/default')
            ->assertNotFound()
            ->assertJsonPath('message', 'Moyen de paiement introuvable.');
    }

    public function test_destroy_returns_not_found_without_stripe_customer(): void
    {
        $this->actingAsUser();

        $this->deleteJson('/api/payment-methods/pm_missing')
            ->assertNotFound()
            ->assertJsonPath('message', 'Moyen de paiement introuvable.');
    }
}
