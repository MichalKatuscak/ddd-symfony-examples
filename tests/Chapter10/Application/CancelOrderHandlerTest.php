<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Application;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter10_Authorization\Application\Command\CancelOrderCommand;
use App\Chapter10_Authorization\Application\Exception\AccessDeniedDomainException;
use App\Chapter10_Authorization\Application\Handler\CancelOrderHandler;
use App\Chapter10_Authorization\Domain\Order\Order;
use App\Chapter10_Authorization\Domain\SystemActor;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CancelOrderHandlerTest extends TestCase
{
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    private InMemoryOrderRepository $orders;

    /** Sběrná sběrnice: jen si zapamatuje, co handler odeslal. */
    private MessageBusInterface $eventBus;

    private CancelOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orders = new InMemoryOrderRepository();
        $this->eventBus = new class implements MessageBusInterface {
            /** @var list<object> */
            public array $messages = [];

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->messages[] = $message;

                return Envelope::wrap($message, $stamps);
            }
        };

        $this->handler = new CancelOrderHandler(
            $this->orders,
            $this->createStub(EntityManagerInterface::class),
            $this->eventBus,
        );
    }

    public function testOwnerCancelsAndEventLeavesThroughBus(): void
    {
        $order = $this->givenOrderOf(self::OWNER);

        ($this->handler)(new CancelOrderCommand($order->id, 'changed mind', CustomerId::fromString(self::OWNER)));

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame(1, $this->orders->saves);
        self::assertCount(1, $this->eventBus->messages);
        self::assertInstanceOf(OrderCancelled::class, $this->eventBus->messages[0]);
    }

    public function testForeignActorIsRefused(): void
    {
        $order = $this->givenOrderOf(self::OWNER);

        $this->expectException(AccessDeniedDomainException::class);
        ($this->handler)(new CancelOrderCommand($order->id, 'cizí storno', CustomerId::fromString(self::STRANGER)));
    }

    public function testSystemActorMayCompensateLockedOrder(): void
    {
        $order = $this->givenOrderOf(self::OWNER);
        $order->lockForSaga();

        // Kompenzace ságy nesmí ztroskotat na zámku, který drží ona sama.
        ($this->handler)(new CancelOrderCommand($order->id, 'kompenzace', CustomerId::fromString(SystemActor::ID)));

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function testOwnerCannotCancelWhileSagaRuns(): void
    {
        $order = $this->givenOrderOf(self::OWNER);
        $order->lockForSaga();

        $this->expectException(OrderLockedBySagaException::class);
        ($this->handler)(new CancelOrderCommand($order->id, 'teď ne', CustomerId::fromString(self::OWNER)));
    }

    private function givenOrderOf(string $customerId): Order
    {
        // Potvrzeno teď: handler si čas storna bere sám, lhůta tedy běží.
        $order = Order::placeWithFirstItem(
            CustomerId::fromString($customerId),
            ProductId::generate(),
            1,
            new Money(30_000, Currency::CZK),
        );
        $order->releaseEvents();
        $this->orders->save($order);
        $this->orders->saves = 0;

        return $order;
    }
}
