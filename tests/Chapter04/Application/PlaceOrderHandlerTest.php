<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Application;

use App\Chapter04_Implementation\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter04_Implementation\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Order\OrderLine;
use App\Chapter04_Implementation\Domain\Order\OrderPlaced;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PlaceOrderHandlerTest extends TestCase
{
    private function makeRepo(): object
    {
        return new class implements OrderRepositoryInterface {
            public ?Order $saved = null;

            public function save(Order $order): void
            {
                $this->saved = $order;
            }

            public function findById(OrderId $id): ?Order
            {
                return null;
            }

            public function findAll(): array
            {
                return [];
            }
        };
    }

    private function makeDispatcher(): object
    {
        return new class implements EventDispatcherInterface {
            /** @var object[] */
            public array $dispatched = [];

            public function dispatch(object $event, ?string $eventName = null): object
            {
                $this->dispatched[] = $event;
                return $event;
            }
        };
    }

    public function test_places_order_and_returns_id(): void
    {
        $repo = $this->makeRepo();
        $dispatcher = $this->makeDispatcher();
        $handler = new PlaceOrderHandler($repo, $dispatcher);

        $orderId = ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-1',
            lines: [new OrderLine('Symfony kniha', 1, new Money(59900, 'CZK'))],
        ));

        $this->assertNotEmpty($orderId);
    }

    public function test_order_is_persisted_via_repository(): void
    {
        $repo = $this->makeRepo();
        $dispatcher = $this->makeDispatcher();
        $handler = new PlaceOrderHandler($repo, $dispatcher);

        $orderId = ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-1',
            lines: [new OrderLine('Produkt', 2, new Money(10000, 'CZK'))],
        ));

        $this->assertNotNull($repo->saved);
        $this->assertSame($orderId, $repo->saved->id()->value);
    }

    public function test_domain_events_are_dispatched(): void
    {
        $repo = $this->makeRepo();
        $dispatcher = $this->makeDispatcher();
        $handler = new PlaceOrderHandler($repo, $dispatcher);

        ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-1',
            lines: [new OrderLine('DDD kniha', 1, new Money(79900, 'CZK'))],
        ));

        $this->assertNotEmpty($dispatcher->dispatched);
        $this->assertInstanceOf(OrderPlaced::class, $dispatcher->dispatched[0]);
    }

    public function test_dispatched_event_contains_correct_customer_id(): void
    {
        $repo = $this->makeRepo();
        $dispatcher = $this->makeDispatcher();
        $handler = new PlaceOrderHandler($repo, $dispatcher);

        ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-42',
            lines: [new OrderLine('Produkt', 1, new Money(5000, 'CZK'))],
        ));

        /** @var OrderPlaced $event */
        $event = $dispatcher->dispatched[0];
        $this->assertSame('zákazník-42', $event->customerId);
    }
}
