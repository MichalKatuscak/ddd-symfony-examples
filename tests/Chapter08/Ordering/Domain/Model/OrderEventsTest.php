<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Ordering\Domain\Model;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\Tests\Chapter08\Shared\Domain\DomainEventAssertions;
use PHPUnit\Framework\TestCase;

final class OrderEventsTest extends TestCase
{
    use DomainEventAssertions;

    public function testOrderPlacedEventContainsCorrectData(): void
    {
        $orderId    = OrderId::generate();
        $customerId = CustomerId::generate();
        $order      = Order::place($orderId, $customerId);
        $order->addItem(ProductId::generate(), 3, new Money(25000, Currency::CZK));

        $events       = $order->releaseEvents();
        $createdEvent = $this->assertSingleEventOfType(OrderPlaced::class, $events);

        // Ověření dat události
        $this->assertTrue($orderId->equals($createdEvent->orderId));
        $this->assertTrue($customerId->equals($createdEvent->customerId));
        $this->assertNotNull($createdEvent->occurredAt);
    }

    public function testNoOrderConfirmedEventWhenOrderNotConfirmed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));

        $events = $order->releaseEvents();

        $this->assertNoEventOfType(OrderConfirmed::class, $events);
    }
}
