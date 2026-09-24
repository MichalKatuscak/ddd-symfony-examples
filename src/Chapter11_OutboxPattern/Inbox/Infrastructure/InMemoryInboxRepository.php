<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Inbox\Infrastructure;

use App\Chapter11_OutboxPattern\Inbox\Application\InboxRepository;
use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

/**
 * Inbox v paměti pro stránku ukázky. Chová se jako tabulka s UNIQUE
 * (event_id, consumer): duplicitní zápis odmítne stejnou výjimkou, jakou
 * by vyhodila databáze, a nikdy ho tiše nepřepíše.
 */
#[AsAlias(InboxRepository::class)]
final class InMemoryInboxRepository implements InboxRepository
{
    /** @var array<string, array{eventId: string, consumer: string, processedAt: \DateTimeImmutable}> */
    private array $processed = [];

    public function isProcessed(Uuid $eventId, string $consumer): bool
    {
        return isset($this->processed[$this->key($eventId, $consumer)]);
    }

    public function markProcessed(Uuid $eventId, string $consumer): void
    {
        $key = $this->key($eventId, $consumer);

        if (isset($this->processed[$key])) {
            throw new UniqueConstraintViolationException(
                new class('UNIQUE constraint failed: inbox.event_id, inbox.consumer') extends AbstractException {},
                null,
            );
        }

        $this->processed[$key] = [
            'eventId' => (string) $eventId,
            'consumer' => $consumer,
            'processedAt' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Jen pro výpis v ukázce.
     *
     * @return list<array{eventId: string, consumer: string, processedAt: \DateTimeImmutable}>
     */
    public function all(): array
    {
        return array_values($this->processed);
    }

    private function key(Uuid $eventId, string $consumer): string
    {
        return $consumer . '::' . $eventId;
    }
}
