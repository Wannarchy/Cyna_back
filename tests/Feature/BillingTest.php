<?php

namespace Tests\Feature;

use Tests\TestCase;

class BillingTest extends TestCase
{
    public function test_config_returns_stripe_publishable_key(): void
    {
        config(['cashier.key' => 'pk_test_example']);

        $this->getJson('/api/billing/config')
            ->assertOk()
            ->assertJsonPath('data.stripe_key', 'pk_test_example');
    }
}
