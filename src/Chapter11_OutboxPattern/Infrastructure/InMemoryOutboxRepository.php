<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Infrastructure;

use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxMessage;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxStatus;

final class InMemoryOutboxRepository implements OutboxRepositoryInterface
{
    /** @var array<string, OutboxMessage> */
    private array $messages = [];

    public function save(OutboxMessage $message): void
    {
        $this->messages[$message->id] = $message;
    }

    public function findPending(int $limit = 100): array
    {
        $pending = array_filter(
            $this->messages,
            static fn (OutboxMessage $m): bool => $m->status() === OutboxStatus::Pending,
        );

        // V produkci by SQL vypadalo:
        //   SELECT * FROM outbox WHERE status = 'pending'
        //   ORDER BY occurred_at ASC LIMIT :limit FOR UPDATE SKIP LOCKED
        usort(
            $pending,
            static fn (OutboxMessage $a, OutboxMessage $b): int
                => $a->occurredAt <=> $b->occurredAt,
        );

        return array_slice(array_values($pending), 0, $limit);
    }

    public function findAll(): array
    {
        $all = array_values($this->messages);
        usort(
            $all,
            static fn (OutboxMessage $a, OutboxMessage $b): int
                => $a->occurredAt <=> $b->occurredAt,
        );

        return $all;
    }
}
