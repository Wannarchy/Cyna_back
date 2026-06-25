<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;
    use RefreshDatabase;

    public function test_validate_percent_promo_returns_discount(): void
    {
        $this->actingAsUser();
        $this->createPromoCode(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]);

        $response = $this->postJson('/api/promo-codes/validate', [
            'code' => 'save10',
            'amount' => 200,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.discount', 20)
            ->assertJsonPath('data.final_amount', 180);
    }

    public function test_validate_rejects_expired_promo(): void
    {
        $this->actingAsUser();
        $this->createPromoCode([
            'code' => 'OLD',
            'expires_at' => now()->subDay()->toDateString(),
        ]);

        $this->postJson('/api/promo-codes/validate', [
            'code' => 'OLD',
            'amount' => 100,
        ])->assertStatus(422);
    }

    public function test_validate_rejects_inactive_promo(): void
    {
        $this->actingAsUser();
        $this->createPromoCode(['code' => 'OFF', 'is_active' => false]);

        $this->postJson('/api/promo-codes/validate', [
            'code' => 'OFF',
            'amount' => 100,
        ])->assertStatus(422);
    }

    public function test_validate_rejects_when_min_amount_not_reached(): void
    {
        $this->actingAsUser();
        $this->createPromoCode(['code' => 'MIN50', 'min_amount' => 50]);

        $this->postJson('/api/promo-codes/validate', [
            'code' => 'MIN50',
            'amount' => 30,
        ])->assertStatus(422);
    }
}
