<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminPromoCodeCrudTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    public function test_admin_can_create_update_and_delete_promo_code(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/api/admin/promo-codes', [
            'code' => 'summer15',
            'type' => 'percent',
            'value' => 15,
            'min_amount' => 100,
            'max_uses' => 5,
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.code', 'SUMMER15')
            ->assertJsonPath('data.value', '15.00');

        $promoId = $create->json('data.id');

        $this->putJson('/api/admin/promo-codes/'.$promoId, [
            'value' => 20,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.value', '20.00')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson('/api/admin/promo-codes/'.$promoId)
            ->assertOk()
            ->assertJsonPath('message', 'Code promo supprimé.');

        $this->assertDatabaseMissing('promo_codes', ['id' => $promoId]);
    }

    public function test_admin_promo_code_validation_rejects_duplicate_code(): void
    {
        $this->actingAsAdmin();
        $this->createPromoCode(['code' => 'DUPLICATE']);

        $this->postJson('/api/admin/promo-codes', [
            'code' => 'DUPLICATE',
            'type' => 'fixed',
            'value' => 5,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
