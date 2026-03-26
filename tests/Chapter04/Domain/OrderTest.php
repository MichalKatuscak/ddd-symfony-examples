<?php

namespace App\Tests\Chapter04\Domain;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Order\OrderPlaced;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_order_raises_domain_event_when_placed(): void
    {
        $order = Order::place(OrderId::generate(), 'zákazník-1', [
            ['name' => 'Symfony kniha', 'qty' => 1, 'price' => 59900],
        ]);

        $events = $order->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
    }

    public function test_domain_service_applies_discount(): void
    {
        $service = new OrderPricingService();
        $price = $service->applyVolumeDiscount(new Money(100000, 'CZK'), 3);
        $this->assertEquals(new Money(90000, 'CZK'), $price); // 10% sleva
    }
}
