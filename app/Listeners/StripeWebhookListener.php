<?php

namespace App\Listeners;

use App\Models\ProductSubscription;
use App\Services\OrderFulfillmentService;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookListener
{
    public function __construct(
        private OrderFulfillmentService $orderFulfillment,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;

        match ($type) {
            'checkout.session.completed' => $this->orderFulfillment->fulfillCheckoutSession(
                $this->asObject($payload['data']['object'] ?? [])
            ),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload['data']['object']),
            'invoice.payment_succeeded' => $this->handleInvoicePaid($payload['data']['object']),
            default => null,
        };
    }

    private function asObject(mixed $value): object
    {
        if (is_object($value)) {
            return $value;
        }

        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    private function handleSubscriptionDeleted(array $subscription): void
    {
        ProductSubscription::query()
            ->where('stripe_subscription_id', $subscription['id'])
            ->where('status', 'active')
            ->each(function (ProductSubscription $productSubscription): void {
                $productSubscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $productSubscription->cancelled_at ?? now(),
                ]);
            });
    }

    private function handleInvoicePaid(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        ProductSubscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->where('status', 'active')
            ->whereNull('cancelled_at')
            ->each(function (ProductSubscription $subscription) {
                $nextBilling = $subscription->cycle === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth();

                $subscription->update([
                    'next_billing' => $nextBilling->toDateString(),
                    'renewal_notified' => false,
                ]);
            });
    }
}
