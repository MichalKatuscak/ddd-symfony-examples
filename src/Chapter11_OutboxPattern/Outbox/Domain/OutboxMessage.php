<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Domain;

use Symfony\Component\Uid\Uuid;

/**
 * Řádek outbox tabulky. Jedenáct vlastností odpovídá jedenácti sloupcům
 * ze sekce 15.03: id, message_type, aggregate_type, aggregate_id, payload,
 * status, occurred_at, attempts, available_at, sent_at, last_error.
 *
 * Kniha třídu mapuje Doctrine atributy na tabulku `outbox` s indexem
 * (status, occurred_at). Ukázka drží řádky v paměti (InMemoryOutboxRepository),
 * takže atributy odpadají a tvar třídy zůstává. Jeden rozdíl z toho plyne:
 * v paměti se occurredAt porovnává i s mikrosekundami, kdežto sloupec typu
 * datetime_immutable v DBAL 4 je neuloží a relay pak řadí jen na sekundy.
 */
class OutboxMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public Uuid $id,
        /** Plně kvalifikovaný název třídy integrační události. */
        public string $messageType,
        /** Typ agregátu, který událost vydal – routovací klíč pro CDC. */
        public string $aggregateType,
        /** ID agregátu – klíč partition, drží pořadí per agregát. */
        public string $aggregateId,
        /** Serializovaný payload události. */
        public array $payload,
        /** pending | sent | failed */
        public string $status = 'pending',
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        public int $attempts = 0,
        // Nejdřívější čas dalšího pokusu. Bez něj relay opakuje okamžitě.
        public \DateTimeImmutable $availableAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $sentAt = null,
        public ?string $lastError = null,
    ) {}

    public function markSent(\DateTimeImmutable $now): void
    {
        $this->status = 'sent';
        $this->sentAt = $now;
        $this->lastError = null;
    }

    public function markFailed(string $error): void
    {
        $this->attempts += 1;
        // Jeden neúspěch ještě není konec: řádek zůstává pending a do failed
        // propadne až po pátém pokusu.
        $this->status = $this->attempts >= 5 ? 'failed' : 'pending';
        // Bez odkladu vezme další iterace relaye řádek okamžitě znovu
        // a všech pět pokusů se vyčerpá během jediné vteřiny.
        $this->availableAt = new \DateTimeImmutable(
            sprintf('+%d seconds', 2 ** $this->attempts),
        );
        $this->lastError = $error;
    }

    public static function fromIntegrationEvent(
        object $event,
        string $aggregateType,
        string $aggregateId,
        callable $serializer,
    ): self {
        return new self(
            id: Uuid::v7(),
            messageType: $event::class,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            payload: $serializer($event),
        );
    }
}
