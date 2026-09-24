<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Outbox;

use App\Chapter11_OutboxPattern\Inbox\Infrastructure\InMemoryInboxRepository;
use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Ordering\Infrastructure\InMemoryOrderRepository;
use App\Chapter11_OutboxPattern\Outbox\Application\DomainEventSerializer;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxMessageFactory;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRelay;
use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InMemoryOutboxRepository;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InProcessPublisher;
use App\Chapter11_OutboxPattern\Reporting\Application\Subscriber\OrderPlacedReadModelUpdater;
use App\Chapter11_OutboxPattern\Reporting\Infrastructure\InMemoryReadModelStore;
use App\Tests\Chapter11\TestSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OutboxRelayTest extends TestCase
{
    private InMemoryOutboxRepository $outbox;
    private InMemoryReadModelStore $readModel;
    private InProcessPublisher $publisher;
    private OutboxRelay $relay;
    private PlaceOrderHandler $placeOrder;

    protected function setUp(): void
    {
        $serializer = TestSerializer::create();

        $this->outbox = new InMemoryOutboxRepository();
        $this->readModel = new InMemoryReadModelStore();
        $this->publisher = new InProcessPublisher(
            new OrderPlacedReadModelUpdater(new InMemoryInboxRepository(), $this->readModel),
        );
        $this->relay = new OutboxRelay($this->outbox, new OutboxMessageFactory($serializer), $this->publisher);
        $this->placeOrder = new PlaceOrderHandler(
            new InMemoryOrderRepository(),
            $this->outbox,
            new DomainEventSerializer($serializer),
        );
    }

    public function test_relay_publishes_pending_row_and_marks_it_sent(): void
    {
        $orderId = $this->placeOrder();

        $result = $this->relay->dispatchPending();

        self::assertSame(['processed' => 1, 'failed' => 0], $result);
        self::assertSame('sent', $this->outbox->all()[0]->status);
        self::assertNotNull($this->outbox->all()[0]->sentAt);
        // Payload prošel serializací tam i zpět a subscriber ho zapsal.
        self::assertSame(1, $this->readModel->find($orderId)['writes'] ?? null);
    }

    public function test_broker_outage_leaves_row_pending_with_backoff(): void
    {
        $this->placeOrder();
        $this->publisher->simulateOutage();

        $result = $this->relay->dispatchPending();

        self::assertSame(['processed' => 0, 'failed' => 1], $result);
        $row = $this->outbox->all()[0];
        self::assertSame('pending', $row->status, 'Výpadek brokera řádek neodepíše.');
        self::assertSame(1, $row->attempts);
        self::assertStringContainsString('nedostupný', (string) $row->lastError);

        // Odklad ještě neuplynul: další průchod řádek nevezme, ani když broker naskočí.
        $this->publisher->simulateOutage(false);
        self::assertSame(['processed' => 0, 'failed' => 0], $this->relay->dispatchPending());

        $row->availableAt = new \DateTimeImmutable('-1 second');
        self::assertSame(['processed' => 1, 'failed' => 0], $this->relay->dispatchPending());
        self::assertSame('sent', $row->status);
    }

    public function test_redelivery_after_crash_before_mark_sent_is_absorbed_by_inbox(): void
    {
        $orderId = $this->placeOrder();
        $this->relay->dispatchPending();

        // Relay publikoval, ale spadl dřív, než řádek označil jako sent.
        $row = $this->outbox->all()[0];
        $row->status = 'pending';
        $row->sentAt = null;

        self::assertSame(['processed' => 1, 'failed' => 0], $this->relay->dispatchPending());
        self::assertSame(1, $this->readModel->find($orderId)['writes'] ?? null);
    }

    public function test_unknown_message_type_is_not_instantiated(): void
    {
        $this->outbox->store(new OutboxMessage(
            id: Uuid::v7(),
            messageType: \stdClass::class,
            aggregateType: 'Order',
            aggregateId: (string) Uuid::v7(),
            payload: [],
        ));

        self::assertSame(['processed' => 0, 'failed' => 1], $this->relay->dispatchPending());
        self::assertStringContainsString('Neznámý message_type', (string) $this->outbox->all()[0]->lastError);
    }

    private function placeOrder(): string
    {
        return ($this->placeOrder)(new PlaceOrder((string) Uuid::v7(), [
            ['productId' => (string) Uuid::v7(), 'quantity' => 1, 'unitPriceInCents' => 1000],
        ]))->value;
    }
}
