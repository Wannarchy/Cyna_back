<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;
    use RefreshDatabase;

    public function test_cancel_schedules_cancellation_while_keeping_active_status(): void
    {
        $user = $this->actingAsUser();
        $product = $this->createProduct();
        $order = Order::create([
            'user_id' => $user->id,
            'total' => 100,
            'subtotal' => 100,
            'tax_amount' => 0,
            'promo_discount' => 0,
            'billing_name' => 'Test User',
            'billing_address' => '1 rue Test',
            'status' => 'paid',
        ]);

        $subscription = ProductSubscription::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'cycle' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'next_billing' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->postJson('/api/subscriptions/'.$subscription->id.'/cancel');

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_change_cycle_updates_subscription_without_stripe_id(): void
    {
        $user = $this->actingAsUser();
        $product = $this->createProduct(attributes: [
            'price_monthly' => 149.00,
            'price_yearly' => 1490.00,
            'stripe_price_id_monthly' => 'price_monthly_test',
            'stripe_price_id_yearly' => 'price_yearly_test',
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'total' => 149,
            'subtotal' => 149,
            'tax_amount' => 0,
            'promo_discount' => 0,
            'billing_name' => 'Test User',
            'billing_address' => '1 rue Test',
            'status' => 'paid',
        ]);

        $subscription = ProductSubscription::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'cycle' => 'monthly',
            'price' => 149,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'next_billing' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->postJson('/api/subscriptions/'.$subscription->id.'/change-cycle', [
            'cycle' => 'yearly',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.cycle', 'yearly')
            ->assertJsonPath('data.price', '1490.00');

        $this->assertDatabaseHas('product_subscriptions', [
            'id' => $subscription->id,
            'cycle' => 'yearly',
            'price' => 1490.00,
        ]);
    }

    public function test_change_cycle_rejects_same_cycle(): void
    {
        $user = $this->actingAsUser();
        $product = $this->createProduct();
        $order = Order::create([
            'user_id' => $user->id,
            'total' => 100,
            'subtotal' => 100,
            'tax_amount' => 0,
            'promo_discount' => 0,
            'billing_name' => 'Test User',
            'billing_address' => '1 rue Test',
            'status' => 'paid',
        ]);

        $subscription = ProductSubscription::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'cycle' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'next_billing' => now()->addMonth()->toDateString(),
        ]);

        $this->postJson('/api/subscriptions/'.$subscription->id.'/change-cycle', [
            'cycle' => 'monthly',
        ])->assertStatus(422);
    }
}
