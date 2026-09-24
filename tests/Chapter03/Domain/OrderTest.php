<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\EmptyOrderException;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_is_draft(): void
    {
        $customerId = CustomerId::generate();
        $order = Order::place(OrderId::generate(), $customerId);

        self::assertSame(OrderStatus::Draft, $order->status());
        self::assertFalse($order->isConfirmed());
        self::assertTrue($order->customerId->equals($customerId));
    }

    public function test_total_is_computed_from_items(): void
    {
        $order = $this->draft();
        $order->addItem(ProductId::generate(), 2, new Money(59_900, Currency::CZK));
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));

        self::assertTrue($order->totalAmount()->equals(new Money(129_800, Currency::CZK)));
        self::assertSame(2, $order->itemCount());
    }

    public function test_empty_order_has_no_total(): void
    {
        $this->expectException(EmptyOrderException::class);
        $this->draft()->totalAmount();
    }

    public function test_mixed_currencies_do_not_pass_silently(): void
    {
        $order = $this->draft();
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
        $order->addItem(ProductId::generate(), 1, new Money(400, Currency::EUR));

        $this->expectException(\DomainException::class);
        $order->totalAmount();
    }

    public function test_quantity_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->draft()->addItem(ProductId::generate(), 0, new Money(100, Currency::CZK));
    }

    public function test_cannot_add_item_to_confirmed_order(): void
    {
        $order = $this->confirmed();

        // Typ výjimky je součást kontraktu – proto se testuje ten,
        // ne obecný předek.
        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->addItem(ProductId::generate(), 1, new Money(5_000, Currency::CZK));
    }

    public function test_remove_item(): void
    {
        $order = $this->draft();
        $keep = ProductId::generate();
        $remove = ProductId::generate();
        $order->addItem($keep, 1, new Money(100, Currency::CZK));
        $order->addItem($remove, 1, new Money(200, Currency::CZK));

        $order->removeItem($remove);

        self::assertSame(1, $order->itemCount());
        self::assertTrue($order->items()[0]->productId->equals($keep));
    }

    public function test_cannot_remove_item_from_confirmed_order(): void
    {
        $order = $this->confirmed();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->removeItem($order->items()[0]->productId);
    }

    public function test_cannot_confirm_empty_order(): void
    {
        $this->expectException(EmptyOrderException::class);
        $this->draft()->confirm();
    }

    public function test_cannot_confirm_twice(): void
    {
        $order = $this->confirmed();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->confirm();
    }

    public function test_confirmed_order_can_be_cancelled(): void
    {
        $order = $this->confirmed();

        $order->cancel('Zákazník si to rozmyslel', new \DateTimeImmutable());

        self::assertSame(OrderStatus::Cancelled, $order->status());
    }

    private function draft(): Order
    {
        return Order::place(OrderId::generate(), CustomerId::generate());
    }

    private function confirmed(): Order
    {
        $order = $this->draft();
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
        $order->confirm();

        return $order;
    }
}
