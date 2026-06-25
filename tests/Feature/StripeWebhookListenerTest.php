<?php

namespace Tests\Feature;

use App\Listeners\StripeWebhookListener;
use App\Models\Order;
use App\Models\ProductSubscription;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class StripeWebhookListenerTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    private StripeWebhookListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = app(StripeWebhookListener::class);
    }

    public function test_checkout_session_completed_creates_order(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $product = $this->createProduct(attributes: [
            'stripe_price_id_monthly' => 'price_monthly_test',
        ]);

        $this->listener->handle(new WebhookReceived([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_webhook_123',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'items' => json_encode([['product_id' => $product->id, 'cycle' => 'monthly']]),
                        'billing_name' => 'Jean Dupont',
                        'billing_address' => '1 rue Test',
                        'promo_code' => '',
                    ],
                    'subscription' => 'sub_webhook_123',
                ],
            ],
        ]));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'stripe_checkout_session_id' => 'cs_webhook_123',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('product_subscriptions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'stripe_subscription_id' => 'sub_webhook_123',
            'status' => 'active',
        ]);
    }

    public function test_customer_subscription_deleted_cancels_product_subscription(): void
    {
        $user = $this->createUser();
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
            'stripe_subscription_id' => 'sub_delete_me',
            'start_date' => now()->toDateString(),
            'next_billing' => now()->addMonth()->toDateString(),
        ]);

        $this->listener->handle(new WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_delete_me',
                ],
            ],
        ]));

        $subscription->refresh();
        $this->assertSame('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_invoice_payment_succeeded_updates_next_billing(): void
    {
        $user = $this->createUser();
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
            'stripe_subscription_id' => 'sub_renew_me',
            'start_date' => now()->toDateString(),
            'next_billing' => now()->subDay()->toDateString(),
            'renewal_notified' => true,
        ]);

        $this->listener->handle(new WebhookReceived([
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'subscription' => 'sub_renew_me',
                ],
            ],
        ]));

        $subscription->refresh();
        $this->assertEquals(
            now()->addMonth()->toDateString(),
            $subscription->next_billing instanceof \DateTimeInterface
                ? $subscription->next_billing->format('Y-m-d')
                : $subscription->next_billing
        );
        $this->assertFalse($subscription->renewal_notified);
    }
}
