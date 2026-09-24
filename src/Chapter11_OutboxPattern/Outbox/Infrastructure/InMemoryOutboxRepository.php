<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Infrastructure;

use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRepository;
use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use Symfony\Component\Uid\Uuid;

/**
 * Náhrada DoctrineOutboxRepository z knihy. Drží stejná dvě pravidla:
 * store() nic „necommituje“ a fetchPending() filtruje i podle availableAt,
 * jinak by backoff z markFailed() nic neznamenal.
 */
final class InMemoryOutboxRepository implements OutboxRepository
{
    /** @var array<string, OutboxMessage> */
    private array $messages = [];

    public function store(OutboxMessage $message): void
    {
        $this->messages[(string) $message->id] = $message;
    }

    public function fetchPending(int $limit = 100): array
    {
        $now = new \DateTimeImmutable();

        // SQL v knize: WHERE status = 'pending' AND available_at <= :now
        //              ORDER BY occurred_at ASC LIMIT :limit
        $pending = array_filter(
            $this->messages,
            static fn (OutboxMessage $m): bool => $m->status === 'pending' && $m->availableAt <= $now,
        );

        usort(
            $pending,
            static fn (OutboxMessage $a, OutboxMessage $b): int => $a->occurredAt <=> $b->occurredAt,
        );

        return array_slice($pending, 0, $limit);
    }

    public function markSent(Uuid $id): void
    {
        ($this->messages[(string) $id] ?? null)?->markSent(new \DateTimeImmutable());
    }

    public function markFailed(Uuid $id, string $error): void
    {
        ($this->messages[(string) $id] ?? null)?->markFailed($error);
    }

    /**
     * Jen pro výpis v ukázce – relay ho nepoužívá.
     *
     * @return list<OutboxMessage>
     */
    public function all(): array
    {
        $all = array_values($this->messages);
        usort($all, static fn (OutboxMessage $a, OutboxMessage $b): int => $a->occurredAt <=> $b->occurredAt);

        return $all;
    }
}
