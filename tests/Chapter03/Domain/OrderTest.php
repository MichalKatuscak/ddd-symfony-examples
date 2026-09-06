<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\EmptyOrderException;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_is_draft(): void
    {
        $order = Order::place(OrderId::generate(), 'zákazník-1');
        $this->assertSame(OrderStatus::Draft, $order->status);
    }

    public function test_can_add_item_to_draft_order(): void
    {
        $order = Order::place(OrderId::generate(), 'zákazník-1');
        $order->addItem(ProductId::generate(), 2, new Money(59900, Currency::CZK));
        $this->assertEquals(new Money(119800, Currency::CZK), $order->totalAmount());
    }

    public function test_cannot_add_item_to_confirmed_order(): void
    {
        // Typ výjimky je součást kontraktu – proto se testuje ten,
        // ne obecný předek.
        $this->expectException(InvalidOrderStateTransitionException::class);
        $order = Order::place(OrderId::generate(), 'zákazník-1');
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
        $order->confirm();
        $order->addItem(ProductId::generate(), 1, new Money(5000, Currency::CZK));
    }

    public function test_cannot_confirm_empty_order(): void
    {
        $this->expectException(EmptyOrderException::class);
        $order = Order::place(OrderId::generate(), 'zákazník-1');
        $order->confirm();
    }
}
