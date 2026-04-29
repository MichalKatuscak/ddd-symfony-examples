<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Application;

use App\Chapter11_OutboxPattern\Application\OnOrderPlaced\OrderPlacedReadModelUpdater;
use App\Chapter11_OutboxPattern\Domain\Order\OrderPlaced;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryInboxRepository;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryReadModelStore;
use PHPUnit\Framework\TestCase;

final class OrderPlacedReadModelUpdaterTest extends TestCase
{
    private InMemoryInboxRepository $inbox;
    private InMemoryReadModelStore $readModel;
    private OrderPlacedReadModelUpdater $updater;

    protected function setUp(): void
    {
        $this->inbox = new InMemoryInboxRepository();
        $this->readModel = new InMemoryReadModelStore();
        $this->updater = new OrderPlacedReadModelUpdater($this->inbox, $this->readModel);
    }

    public function test_first_delivery_updates_read_model_and_marks_inbox(): void
    {
        $event = $this->event('event-1', 'order-1');

        ($this->updater)($event);

        $row = $this->readModel->findByOrderId('order-1');
        $this->assertNotNull($row);
        $this->assertSame(1, $row['updatesApplied']);
        $this->assertTrue($this->inbox->isProcessed(
            OrderPlacedReadModelUpdater::CONSUMER_NAME,
            'event-1',
        ));
    }

    public function test_duplicate_delivery_is_idempotent(): void
    {
        $event = $this->event('event-1', 'order-1');

        ($this->updater)($event);
        ($this->updater)($event); // duplicitní doručení (at-least-once)
        ($this->updater)($event); // a ještě jedno pro jistotu

        $row = $this->readModel->findByOrderId('order-1');
        $this->assertSame(
            1,
            $row['updatesApplied'],
            'Read model must be updated exactly once even with duplicate deliveries',
        );
    }

    public function test_different_events_each_update_read_model_once(): void
    {
        ($this->updater)($this->event('event-1', 'order-1'));
        ($this->updater)($this->event('event-2', 'order-2'));
        ($this->updater)($this->event('event-1', 'order-1')); // duplicate of first

        $this->assertCount(2, $this->inbox->findAll());
        $this->assertSame(1, $this->readModel->findByOrderId('order-1')['updatesApplied']);
        $this->assertSame(1, $this->readModel->findByOrderId('order-2')['updatesApplied']);
    }

    private function event(string $eventId, string $orderId): OrderPlaced
    {
        return new OrderPlaced(
            eventId: $eventId,
            orderId: $orderId,
            customerId: 'customer-x',
            amount: 1000,
        );
    }
}
