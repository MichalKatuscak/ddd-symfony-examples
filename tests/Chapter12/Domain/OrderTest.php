<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Domain;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\EmptyOrderException;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\Order;
use App\Chapter12_LesserPatterns\Domain\Order\OrderItem;
use App\Chapter12_LesserPatterns\Domain\Order\OrderPlaced;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_static_factory_method_creates_order_and_records_event(): void
    {
        $order = Order::place(
            customerId: new CustomerId('cust-1'),
            items: [
                new OrderItem('Položka A', 2, new Money(50_000)),
                new OrderItem('Položka B', 1, new Money(30_000)),
            ],
            shippingCountry: 'cz',
        );

        $this->assertSame('CZ', $order->shippingCountry());
        $this->assertSame(130_000, $order->total()->amount);

        $events = $order->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertSame($order->id()->value, $events[0]->orderId->value);
    }

    public function test_static_factory_rejects_empty_items(): void
    {
        $this->expectException(EmptyOrderException::class);

        Order::place(
            customerId: new CustomerId('cust-1'),
            items: [],
            shippingCountry: 'CZ',
        );
    }
}
