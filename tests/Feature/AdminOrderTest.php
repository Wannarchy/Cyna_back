<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    private function createOrder(int $userId, array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $userId,
            'total' => 100,
            'subtotal' => 100,
            'tax_amount' => 16.67,
            'promo_discount' => 0,
            'billing_name' => 'Client Test',
            'billing_address' => '1 rue Test',
            'status' => 'paid',
        ], $attributes));

        $product = $this->createProduct();
        $order->items()->create([
            'product_id' => $product->id,
            'cycle' => 'monthly',
            'price' => 100,
        ]);

        return $order;
    }

    public function test_admin_can_list_and_show_orders(): void
    {
        $admin = $this->actingAsAdmin();
        $order = $this->createOrder($admin->id);

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);

        $this->getJson('/api/admin/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.items.0.product_id', $order->items->first()->product_id);
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->actingAsAdmin();
        $order = $this->createOrder($admin->id, ['status' => 'paid']);

        $this->patchJson('/api/admin/orders/'.$order->id.'/status', [
            'status' => 'refunded',
        ])->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('message', 'Statut de la commande mis à jour.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_admin_order_update_validates_status(): void
    {
        $admin = $this->actingAsAdmin();
        $order = $this->createOrder($admin->id);

        $this->patchJson('/api/admin/orders/'.$order->id.'/status', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
