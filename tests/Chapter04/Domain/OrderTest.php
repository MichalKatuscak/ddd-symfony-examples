<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Domain;

use App\Shared\Domain\Currency;
use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Order\OrderLine;
use App\Chapter04_Implementation\Domain\Order\OrderPlaced;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_order_raises_domain_event_when_placed(): void
    {
        $order = Order::place(OrderId::generate(), 'zákazník-1', [
            new OrderLine('Symfony kniha', 1, new Money(59900, Currency::CZK)),
        ]);

        $events = $order->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
    }

    public function test_domain_service_applies_discount(): void
    {
        $service = new OrderPricingService();
        $price = $service->applyVolumeDiscount(new Money(100000, Currency::CZK), 3);
        $this->assertEquals(new Money(90000, Currency::CZK), $price); // 10% sleva
    }
}
