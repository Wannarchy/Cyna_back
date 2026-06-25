<?php

namespace Tests\Feature;

use App\Models\UserAddress;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use CreatesUsers;

    private function validAddressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Bureau',
            'usage_type' => 'both',
            'prenom' => 'Jean',
            'nom' => 'Dupont',
            'adresse1' => '1 rue Test',
            'ville' => 'Paris',
            'code_postal' => '75001',
            'pays' => 'France',
            'telephone' => '0102030405',
        ], $overrides);
    }

    public function test_addresses_require_authentication(): void
    {
        $this->getJson('/api/addresses')->assertUnauthorized();
    }

    public function test_store_creates_first_address_as_default(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/addresses', $this->validAddressPayload());

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.is_default_shipping', true);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'adresse1' => '1 rue Test',
            'is_default' => true,
            'is_default_shipping' => true,
        ]);
    }

    public function test_update_can_set_only_one_default_billing_address(): void
    {
        $user = $this->actingAsUser();
        $first = UserAddress::create(array_merge($this->validAddressPayload(), [
            'user_id' => $user->id,
            'is_default' => true,
            'is_default_shipping' => true,
        ]));
        $second = UserAddress::create(array_merge($this->validAddressPayload([
            'label' => 'Maison',
            'adresse1' => '2 rue Test',
        ]), [
            'user_id' => $user->id,
            'is_default' => false,
            'is_default_shipping' => false,
        ]));

        $this->putJson('/api/addresses/'.$second->id, [
            'is_default' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_default', true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_update_rejects_other_user_address(): void
    {
        $this->actingAsUser();
        $other = $this->createUser();
        $address = UserAddress::create(array_merge($this->validAddressPayload(), [
            'user_id' => $other->id,
        ]));

        $this->putJson('/api/addresses/'.$address->id, [
            'label' => 'Tentative',
        ])->assertNotFound();
    }

    public function test_destroy_reassigns_default_address(): void
    {
        $user = $this->actingAsUser();
        $first = UserAddress::create(array_merge($this->validAddressPayload(), [
            'user_id' => $user->id,
            'is_default' => true,
            'is_default_shipping' => true,
        ]));
        $second = UserAddress::create(array_merge($this->validAddressPayload([
            'label' => 'Maison',
            'adresse1' => '2 rue Test',
        ]), [
            'user_id' => $user->id,
            'is_default' => false,
            'is_default_shipping' => false,
        ]));

        $this->deleteJson('/api/addresses/'.$first->id)
            ->assertOk()
            ->assertJsonPath('message', 'Adresse supprimée.');

        $this->assertDatabaseMissing('user_addresses', ['id' => $first->id]);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default_shipping);
    }
}
