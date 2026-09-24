<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Reporting;

use App\Chapter11_OutboxPattern\Inbox\Infrastructure\InMemoryInboxRepository;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Reporting\Application\Subscriber\OrderPlacedReadModelUpdater;
use App\Chapter11_OutboxPattern\Reporting\Infrastructure\InMemoryReadModelStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

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
        $event = $this->event();

        ($this->updater)($event);

        self::assertSame(1, $this->readModel->find($event->orderId)['writes'] ?? null);
        self::assertTrue($this->inbox->isProcessed($event->eventId, OrderPlacedReadModelUpdater::CONSUMER));
    }

    public function test_duplicate_delivery_has_no_side_effect(): void
    {
        $event = $this->event();

        ($this->updater)($event);
        ($this->updater)($event); // at-least-once: tatáž událost podruhé
        ($this->updater)($event);

        self::assertSame(1, $this->readModel->find($event->orderId)['writes'] ?? null);
        self::assertCount(1, $this->inbox->all());
    }

    public function test_different_events_are_each_applied_once(): void
    {
        $first = $this->event();
        $second = $this->event();

        ($this->updater)($first);
        ($this->updater)($second);
        ($this->updater)($first);

        self::assertCount(2, $this->inbox->all());
        self::assertSame(1, $this->readModel->find($first->orderId)['writes'] ?? null);
        self::assertSame(1, $this->readModel->find($second->orderId)['writes'] ?? null);
    }

    private function event(): OrderPlacedIntegrationEvent
    {
        return new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: (string) Uuid::v7(),
            customerId: (string) Uuid::v7(),
            items: [['productId' => (string) Uuid::v7(), 'quantity' => 1, 'unitPriceInCents' => 1000]],
            totalAmountCents: 1000,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
