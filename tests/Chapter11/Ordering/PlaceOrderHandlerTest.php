<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Ordering;

use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter11_OutboxPattern\Ordering\Infrastructure\InMemoryOrderRepository;
use App\Chapter11_OutboxPattern\Outbox\Application\DomainEventSerializer;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InMemoryOutboxRepository;
use App\Tests\Chapter11\TestSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PlaceOrderHandlerTest extends TestCase
{
    private InMemoryOrderRepository $orders;
    private InMemoryOutboxRepository $outbox;
    private PlaceOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orders = new InMemoryOrderRepository();
        $this->outbox = new InMemoryOutboxRepository();
        $this->handler = new PlaceOrderHandler(
            $this->orders,
            $this->outbox,
            new DomainEventSerializer(TestSerializer::create()),
        );
    }

    public function test_order_and_single_integration_event_are_stored_together(): void
    {
        $customerId = (string) Uuid::v7();
        $productId = (string) Uuid::v7();

        $orderId = ($this->handler)(new PlaceOrder($customerId, [
            ['productId' => $productId, 'quantity' => 3, 'unitPriceInCents' => 500],
        ]));

        self::assertSame(OrderStatus::Confirmed, $this->orders->get($orderId)->status);

        // placeWithItems() nahraje tři doménové události, do outboxu jde jen
        // integrační tvar OrderPlaced – OrderItemAdded a OrderConfirmed zůstávají uvnitř kontextu.
        $rows = $this->outbox->all();
        self::assertCount(1, $rows);

        $row = $rows[0];
        self::assertSame(OrderPlacedIntegrationEvent::class, $row->messageType);
        self::assertSame('Order', $row->aggregateType);
        self::assertSame($orderId->value, $row->aggregateId);
        self::assertSame('pending', $row->status);
        self::assertSame(0, $row->attempts);
        self::assertNull($row->sentAt);
        self::assertNull($row->lastError);

        self::assertSame($orderId->value, $row->payload['orderId']);
        self::assertSame($customerId, $row->payload['customerId']);
        self::assertSame(1500, $row->payload['totalAmountCents']);
        self::assertSame(
            [['productId' => $productId, 'quantity' => 3, 'unitPriceInCents' => 500]],
            $row->payload['items'],
        );
    }

    public function test_integration_event_carries_its_own_event_id(): void
    {
        ($this->handler)(new PlaceOrder((string) Uuid::v7(), [
            ['productId' => (string) Uuid::v7(), 'quantity' => 1, 'unitPriceInCents' => 100],
        ]));

        $row = $this->outbox->all()[0];

        // eventId je identita události pro Inbox; id řádku outboxu je jiná hodnota.
        self::assertTrue(Uuid::isValid($row->payload['eventId']));
        self::assertNotSame((string) $row->id, $row->payload['eventId']);
    }
}
