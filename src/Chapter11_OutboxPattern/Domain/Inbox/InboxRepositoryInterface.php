<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Inbox;

interface InboxRepositoryInterface
{
    public function isProcessed(string $consumerName, string $eventId): bool;

    public function markProcessed(ProcessedEventId $processed): void;

    /**
     * @return ProcessedEventId[]
     */
    public function findAll(): array;
}
