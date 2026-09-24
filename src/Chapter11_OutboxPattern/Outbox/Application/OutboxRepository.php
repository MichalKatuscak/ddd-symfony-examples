<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use Symfony\Component\Uid\Uuid;

interface OutboxRepository
{
    public function store(OutboxMessage $message): void;

    /**
     * Pending řádky, kterým už uplynul odklad (availableAt), seřazené
     * podle occurredAt – best-effort FIFO, ne garance pořadí.
     *
     * @return list<OutboxMessage>
     */
    public function fetchPending(int $limit = 100): array;

    public function markSent(Uuid $id): void;

    public function markFailed(Uuid $id, string $error): void;
}
