<?php

namespace Tests\Unit;

use App\Notifications\OrderConfirmationNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class OrderFulfillmentServiceTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;
    use RefreshDatabase;

    private OrderFulfillmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderFulfillmentService;
    }

    public function test_calculate_line_items_returns_prices(): void
    {
        $product = $this->createProduct(attributes: [
            'price_monthly' => 149.00,
            'price_yearly' => 1490.00,
        ]);

        $lines = $this->service->calculateLineItems([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]);

        $this->assertCount(1, $lines);
        $this->assertSame(149.00, $lines->first()['price']);
        $this->assertSame('monthly', $lines->first()['cycle']);
    }

    public function test_calculate_line_items_rejects_out_of_stock_product(): void
    {
        $product = $this->createProduct(attributes: ['stock' => 0]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateLineItems([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]);
    }

    public function test_calculate_total_applies_percent_promo(): void
    {
        $product = $this->createProduct(attributes: ['price_monthly' => 200.00]);
        $lines = $this->service->calculateLineItems([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]);

        $this->createPromoCode(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]);

        $total = $this->service->calculateTotal($lines, 'SAVE10');

        $this->assertSame(180.00, $total);
    }

    public function test_calculate_total_applies_fixed_promo_without_going_negative(): void
    {
        $product = $this->createProduct(attributes: ['price_monthly' => 20.00]);
        $lines = $this->service->calculateLineItems([
            ['product_id' => $product->id, 'cycle' => 'monthly'],
        ]);

        $this->createPromoCode(['code' => 'BIG', 'type' => 'fixed', 'value' => 50]);

        $total = $this->service->calculateTotal($lines, 'BIG');

        $this->assertSame(0.00, $total);
    }

    public function test_fulfill_paid_order_decrements_stock(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $product = $this->createProduct(attributes: ['stock' => 5, 'price_monthly' => 100.00]);

        $order = $this->service->fulfill(
            user: $user,
            items: [['product_id' => $product->id, 'cycle' => 'monthly']],
            billingName: 'Test User',
            billingAddress: '1 rue Test',
            stripePaymentIntent: 'pi_test_123',
        );

        $this->assertSame('paid', $order->status);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);

        Notification::assertSentTo($user, OrderConfirmationNotification::class, function (OrderConfirmationNotification $notification) use ($order) {
            return $notification->order->id === $order->id;
        });
    }

    public function test_fulfill_pending_order_does_not_send_confirmation_email(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $product = $this->createProduct(attributes: ['stock' => 5, 'price_monthly' => 100.00]);

        $order = $this->service->fulfill(
            user: $user,
            items: [['product_id' => $product->id, 'cycle' => 'monthly']],
            billingName: 'Test User',
            billingAddress: '1 rue Test',
        );

        $this->assertSame('pending', $order->status);
        Notification::assertNothingSent();
    }
}
