<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_is_pending(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $this->assertSame(OrderStatus::Pending, $order->status());
    }

    public function test_can_add_item_to_pending_order(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Symfony kniha', 2, new Money(59900, 'CZK'));
        $this->assertEquals(new Money(119800, 'CZK'), $order->total());
    }

    public function test_cannot_add_item_to_confirmed_order(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Produkt', 1, new Money(10000, 'CZK'));
        $order->confirm();
        $order->addItem('Další', 1, new Money(5000, 'CZK'));
    }

    public function test_cannot_confirm_empty_order(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->confirm();
    }
}
