<?php

declare(strict_types=1);

namespace App\Tests\Chapter05\Ordering\Application;

use App\Chapter05_CQRS\Ordering\Application\Command\PlaceOrder;
use App\Chapter05_CQRS\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter05_CQRS\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderPlaced;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter05_CQRS\Ordering\Infrastructure\Repository\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class PlaceOrderHandlerTest extends TestCase
{
    private const CUSTOMER = '01920000-0000-7000-8000-000000000001';
    private const BOOK = '01920000-0000-7000-8000-0000000000a1';
    private const STICKER = '01920000-0000-7000-8000-0000000000a3';

    private InMemoryOrderRepository $orders;
    /** @var \ArrayObject<int, object> */
    private \ArrayObject $dispatched;
    private PlaceOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orders = new InMemoryOrderRepository();
        $this->dispatched = new \ArrayObject();
        $eventBus = new class ($this->dispatched) implements MessageBusInterface {
            /** @param \ArrayObject<int, object> $log */
            public function __construct(private readonly \ArrayObject $log) {}

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->log[] = $message;

                return new Envelope($message, $stamps);
            }
        };

        $this->handler = new PlaceOrderHandler($this->orders, $eventBus);
    }

    private function place(): OrderId
    {
        return ($this->handler)(new PlaceOrder(self::CUSTOMER, [
            ['productId' => self::BOOK, 'quantity' => 2, 'unitPriceInCents' => 79900],
            ['productId' => self::STICKER, 'quantity' => 1, 'unitPriceInCents' => 4900],
        ]));
    }

    public function test_returns_order_id_of_saved_confirmed_order(): void
    {
        $orderId = $this->place();

        $order = $this->orders->get($orderId);
        self::assertSame(OrderStatus::Confirmed, $order->status);
        self::assertSame(164700, $order->totalAmount()->amountInCents);
    }

    public function test_dispatches_domain_events_and_single_integration_event(): void
    {
        $orderId = $this->place();

        // Doménové události dostanou posluchači v kontextu; integrační tvar
        // má jen OrderPlaced a nese celou objednávku.
        self::assertSame(
            [
                OrderPlaced::class,
                OrderPlacedIntegrationEvent::class,
                OrderItemAdded::class,
                OrderItemAdded::class,
                OrderConfirmed::class,
            ],
            array_map(static fn (object $m): string => $m::class, $this->dispatched->getArrayCopy()),
        );
        $event = $this->dispatched[1];
        self::assertInstanceOf(OrderPlacedIntegrationEvent::class, $event);
        self::assertSame($orderId->value, $event->orderId);
        self::assertSame(self::CUSTOMER, $event->customerId);
        self::assertSame(164700, $event->totalAmountCents);
        self::assertSame(
            [
                ['productId' => self::BOOK, 'quantity' => 2, 'unitPriceInCents' => 79900],
                ['productId' => self::STICKER, 'quantity' => 1, 'unitPriceInCents' => 4900],
            ],
            $event->items,
        );
    }
}
