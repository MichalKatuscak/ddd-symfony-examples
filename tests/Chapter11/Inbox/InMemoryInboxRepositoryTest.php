<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Inbox;

use App\Chapter11_OutboxPattern\Inbox\Infrastructure\InMemoryInboxRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InMemoryInboxRepositoryTest extends TestCase
{
    public function test_duplicate_mark_is_rejected_not_overwritten(): void
    {
        $inbox = new InMemoryInboxRepository();
        $eventId = Uuid::v7();
        $inbox->markProcessed($eventId, 'reporting.order_placed');

        $this->expectException(UniqueConstraintViolationException::class);
        $inbox->markProcessed($eventId, 'reporting.order_placed');
    }

    public function test_same_event_is_tracked_per_consumer(): void
    {
        $inbox = new InMemoryInboxRepository();
        $eventId = Uuid::v7();

        $inbox->markProcessed($eventId, 'reporting.order_placed');
        $inbox->markProcessed($eventId, 'notifications.order_placed');

        self::assertTrue($inbox->isProcessed($eventId, 'reporting.order_placed'));
        self::assertTrue($inbox->isProcessed($eventId, 'notifications.order_placed'));
        self::assertFalse($inbox->isProcessed($eventId, 'search.order_placed'));
    }
}
