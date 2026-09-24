<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Ordering\Domain\Builder;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderItemAdded;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\Tests\Chapter08\Shared\Domain\DomainEventAssertions;
use PHPUnit\Framework\TestCase;

/**
 * Použití builderu tak, jak ho kniha ukazuje v komentáři pod třídou:
 * test nastaví jen to, na čem mu záleží, zbytek doplní výchozí hodnoty.
 */
final class OrderBuilderTest extends TestCase
{
    use DomainEventAssertions;

    public function testBuildsConfirmedOrderWithGivenItem(): void
    {
        $customerId = CustomerId::generate();

        $order = OrderBuilder::anOrder()
            ->forCustomer($customerId)
            ->withItem(quantity: 3, unitPrice: new Money(25000, Currency::CZK))
            ->confirmed()
            ->build();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertTrue($order->isOwnedBy($customerId));
        $this->assertSame(75000, $order->totalAmount()->amountInCents);
    }

    public function testBuildingRecordsEventsInCanonicalOrder(): void
    {
        $order = OrderBuilder::anOrder()->withItem()->confirmed()->build();

        // Stavba agregátu vydala události; pořadí je OrderPlaced → OrderItemAdded → OrderConfirmed.
        $this->assertEventSequence(
            [OrderPlaced::class, OrderItemAdded::class, OrderConfirmed::class],
            $order->releaseEvents(),
        );
    }

    public function testDefaultItemMakesOrderConfirmable(): void
    {
        // Bez withItem() dostane objednávka bezpečnou výchozí položku,
        // jinak by confirmed() skončilo na EmptyOrderException.
        $order = OrderBuilder::anOrder()->confirmed()->build();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(49900, $order->totalAmount()->amountInCents);
    }
}
