<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Infrastructure;

use App\Chapter11_OutboxPattern\Domain\Inbox\InboxRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Inbox\ProcessedEventId;

final class InMemoryInboxRepository implements InboxRepositoryInterface
{
    /** @var array<string, ProcessedEventId> */
    private array $processed = [];

    public function isProcessed(string $consumerName, string $eventId): bool
    {
        return isset($this->processed[$this->key($consumerName, $eventId)]);
    }

    public function markProcessed(ProcessedEventId $processed): void
    {
        $key = $this->key($processed->consumerName, $processed->eventId);

        // V produkci by toto byl INSERT s UNIQUE constraint na (consumer, event_id) —
        // duplicitní insert by skončil výjimkou a my bychom ho ignorovali.
        $this->processed[$key] = $processed;
    }

    public function findAll(): array
    {
        return array_values($this->processed);
    }

    private function key(string $consumerName, string $eventId): string
    {
        return $consumerName.'::'.$eventId;
    }
}
