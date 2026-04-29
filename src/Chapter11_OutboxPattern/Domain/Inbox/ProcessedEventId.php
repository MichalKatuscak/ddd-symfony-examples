<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Inbox;

/**
 * Záznam v "inbox" tabulce — značí, že daný eventId byl už úspěšně zpracován.
 *
 * V produkci by toto byla Doctrine entita s primárním klíčem na (consumerName, eventId).
 * Idempotency check pak proběhne přes UNIQUE constraint v DB.
 */
final readonly class ProcessedEventId
{
    public function __construct(
        public string $consumerName,
        public string $eventId,
        public \DateTimeImmutable $processedAt = new \DateTimeImmutable(),
    ) {
        if ($consumerName === '') {
            throw new \InvalidArgumentException('Consumer name cannot be empty');
        }
        if ($eventId === '') {
            throw new \InvalidArgumentException('Event id cannot be empty');
        }
    }
}
