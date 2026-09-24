<?php

declare(strict_types=1);

namespace App\Tests\Chapter05\Ordering\Domain;

use App\Chapter05_CQRS\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderPlaced;
use App\Chapter05_CQRS\Ordering\Domain\Exception\EmptyOrderException;
use App\Chapter05_CQRS\Ordering\Domain\Model\Order;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private const BOOK = '01920000-0000-7000-8000-0000000000a1';

    public function test_place_with_items_records_events_in_canonical_order(): void
    {
        $order = Order::placeWithItems(CustomerId::generate(), [
            ['productId' => self::BOOK, 'quantity' => 1, 'unitPriceInCents' => 79900],
            ['productId' => self::BOOK, 'quantity' => 2, 'unitPriceInCents' => 79900],
        ]);

        $types = array_map(static fn (object $e): string => $e::class, $order->releaseEvents());

        self::assertSame(
            [OrderPlaced::class, OrderItemAdded::class, OrderItemAdded::class, OrderConfirmed::class],
            $types,
        );
        self::assertSame(OrderStatus::Confirmed, $order->status);
        self::assertNotNull($order->placedAt);
    }

    public function test_same_product_increases_quantity_instead_of_new_line(): void
    {
        $order = Order::placeWithItems(CustomerId::generate(), [
            ['productId' => self::BOOK, 'quantity' => 1, 'unitPriceInCents' => 79900],
            ['productId' => self::BOOK, 'quantity' => 2, 'unitPriceInCents' => 79900],
        ]);

        self::assertCount(1, $order->items());
        self::assertSame(3, $order->items()[0]->quantity);
        self::assertSame(239700, $order->totalAmount()->amountInCents);
    }

    public function test_order_without_items_cannot_be_confirmed(): void
    {
        $this->expectException(EmptyOrderException::class);

        Order::placeWithItems(CustomerId::generate(), []);
    }
}
