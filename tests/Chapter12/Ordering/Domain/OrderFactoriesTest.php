<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Ordering\Domain;

use App\Chapter12_LesserPatterns\Ordering\Domain\Event\OrderPlaced;
use App\Chapter12_LesserPatterns\Ordering\Domain\Exception\EmptyOrderException;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\DigitalItem;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\OrderType;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\Tests\Chapter12\Ordering\OrderMother;
use PHPUnit\Framework\TestCase;

final class OrderFactoriesTest extends TestCase
{
    public function test_place_physical_records_order_placed(): void
    {
        $order = OrderMother::physical(1_000);

        $events = $order->releaseEvents();

        self::assertSame(OrderType::Physical, $order->type());
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertTrue($order->id->equals($events[0]->orderId));
    }

    public function test_place_physical_rejects_empty_order(): void
    {
        $this->expectException(EmptyOrderException::class);

        Order::placePhysical(
            CustomerId::generate(),
            [],
            new \DateTimeImmutable(),
            new ShippingAddress('Ukázková 1', 'Město', '110 00', 'CZ'),
        );
    }

    public function test_place_digital_converts_items_and_has_no_address(): void
    {
        $order = Order::placeDigital(
            CustomerId::generate(),
            [
                new DigitalItem(ProductId::generate(), new Money(29_900, Currency::CZK)),
                new DigitalItem(ProductId::generate(), new Money(9_900, Currency::CZK)),
            ],
            new \DateTimeImmutable(),
        );

        self::assertSame(OrderType::Digital, $order->type());
        self::assertNull($order->shippingAddress);
        self::assertSame(39_800, $order->totalAmount()->amountInCents);
    }

    public function test_reconstitute_records_no_event(): void
    {
        $original = OrderMother::physical(1_000);

        $restored = Order::reconstitute(
            $original->id,
            $original->customerId,
            $original->items(),
            $original->type(),
            $original->placedAt(),
            $original->shippingAddress,
        );

        // Kdyby event zaznamenával konstruktor, každé načtení z databáze
        // by objednávku „umístilo“ znovu.
        self::assertSame([], $restored->releaseEvents());
        self::assertTrue($original->id->equals($restored->id));
    }
}
