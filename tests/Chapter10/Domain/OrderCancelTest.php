<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Domain;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter10_Authorization\Domain\Order\Exception\CancellationWindowExpiredException;
use App\Chapter10_Authorization\Domain\Order\Order;
use PHPUnit\Framework\TestCase;

final class OrderCancelTest extends TestCase
{
    public function testCancelWithinWindowSucceeds(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');
        $order->releaseEvents(); // vyprázdní eventy z fáze vytvoření

        $order->cancel('changed mind', new \DateTimeImmutable('2026-04-29 12:00:00'));

        // Stav se ověří přes chování: úspěšný cancel zaznamená OrderCancelled
        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCancelled::class, $events[0]);
    }

    public function testCancelOfShippedOrderThrows(): void
    {
        $order = OrderFactory::shipped();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->cancel('changed mind', new \DateTimeImmutable());
    }

    public function testCancelAfter24hThrows(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');

        $this->expectException(CancellationWindowExpiredException::class);
        $order->cancel('too late', new \DateTimeImmutable('2026-04-30 11:00:00'));
    }

    public function testPaidOrderCanBeCancelledWithinWindow(): void
    {
        // Stavová podmínka blokuje jen Shipped a Delivered. Zúžení na
        // Confirmed by rozbilo kompenzaci ságy, která ruší zaplacenou objednávku.
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');
        $order->markPaid();

        $order->cancel('kompenzace', new \DateTimeImmutable('2026-04-29 11:00:00'));

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function testRepeatedCancelIsIdempotent(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');
        $order->cancel('changed mind', new \DateTimeImmutable('2026-04-29 12:00:00'));
        $order->releaseEvents();

        // Retry ságy ani druhé kliknutí nesmí shodit handler – ani po lhůtě.
        $order->cancel('changed mind', new \DateTimeImmutable('2026-05-10 12:00:00'));

        self::assertSame([], $order->releaseEvents());
    }

    public function testDraftHasNoWindow(): void
    {
        // Lhůta běží od potvrzení; rozpracovaný košík nikdo neruší na čas.
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $order->cancel('opuštěný košík', new \DateTimeImmutable('2030-01-01 00:00:00'));

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function testCancelIsRefusedWhileSagaHoldsLock(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');
        $order->lockForSaga();

        $this->expectException(OrderLockedBySagaException::class);
        $order->cancel('teď ne', new \DateTimeImmutable('2026-04-29 11:00:00'));
    }

    public function testIsCancellableFollowsWindowStateAndLock(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');

        self::assertTrue($order->isCancellable(new \DateTimeImmutable('2026-04-30 10:00:00')));
        self::assertFalse($order->isCancellable(new \DateTimeImmutable('2026-04-30 10:00:01')));

        // Tlačítko, které vede na jistou chybu, se nemá nabízet.
        $order->lockForSaga();
        self::assertFalse($order->isCancellable(new \DateTimeImmutable('2026-04-29 11:00:00')));

        self::assertFalse(OrderFactory::shipped()->isCancellable(new \DateTimeImmutable('2026-04-29 11:00:00')));
    }
}
