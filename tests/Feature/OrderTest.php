<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    private function createOrderForUser(int $userId, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $userId,
            'total' => 100,
            'subtotal' => 100,
            'tax_amount' => 16.67,
            'promo_discount' => 0,
            'billing_name' => 'Test User',
            'billing_address' => '1 rue Test',
            'status' => 'paid',
        ], $attributes));
    }

    private function validOrderPayload(array $items): array
    {
        return [
            'billing_name' => 'Jean Dupont',
            'billing_address' => '1 rue Test, Paris',
            'payment_method' => 'pm_card_visa',
            'items' => $items,
        ];
    }

    public function test_orders_require_authentication(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_index_returns_only_authenticated_user_orders(): void
    {
        $user = $this->actingAsUser();
        $other = $this->createUser();

        $ownOrder = $this->createOrderForUser($user->id);
        $this->createOrderForUser($other->id);

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.id', $ownOrder->id);
    }

    public function test_show_rejects_other_user_order(): void
    {
        $this->actingAsUser();
        $other = $this->createUser();
        $order = $this->createOrderForUser($other->id);

        $this->getJson('/api/orders/'.$order->id)
            ->assertNotFound();
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'billing_name',
                'billing_address',
                'payment_method',
                'items',
            ]);
    }

    public function test_store_requires_verified_email(): void
    {
        $user = $this->createUser(['est_confirme' => false]);
        $this->actingAsUser($user);

        $product = $this->createProduct();

        $this->postJson('/api/orders', $this->validOrderPayload([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]))->assertForbidden()
            ->assertJsonPath('email_verification_required', true);
    }

    public function test_store_requires_shipping_address_for_physical_products(): void
    {
        $this->actingAsUser();

        $product = $this->createProduct(attributes: [
            'requires_shipping' => true,
        ]);

        $this->postJson('/api/orders', $this->validOrderPayload([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_address']);
    }
}
