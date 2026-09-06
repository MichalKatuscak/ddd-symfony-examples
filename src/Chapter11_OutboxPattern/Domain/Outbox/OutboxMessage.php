<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Outbox;

use App\Shared\Domain\DomainEvent;

/**
 * Outbox row — řádek v "outbox" tabulce.
 *
 * V produkci by toto byla Doctrine entita mapovaná na tabulku se schématem:
 *   id UUID, type VARCHAR, payload JSONB, occurred_at TIMESTAMP,
 *   status VARCHAR, attempts INT, sent_at TIMESTAMP NULL
 * + index (status, occurred_at) pro relay polling.
 *
 * Pro účely této ukázky je to plain PHP objekt držený v paměti.
 */
final class OutboxMessage
{
    private const MAX_ATTEMPTS = 5;

    private OutboxStatus $status = OutboxStatus::Pending;

    private int $attempts = 0;

    private ?\DateTimeImmutable $sentAt = null;

    private \DateTimeImmutable $availableAt;

    private ?string $lastError = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array $payload,
        public readonly \DateTimeImmutable $occurredAt,
    ) {
        $this->availableAt = $occurredAt;
    }

    public static function fromEvent(DomainEvent $event): self
    {
        $reflection = new \ReflectionObject($event);
        $payload = [];
        foreach ($reflection->getProperties() as $property) {
            $value = $property->getValue($event);
            if ($value instanceof \DateTimeImmutable) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }
            $payload[$property->getName()] = $value;
        }

        $eventId = $payload['eventId'] ?? bin2hex(random_bytes(8));

        return new self(
            id: (string) $eventId,
            type: $event::class,
            payload: $payload,
            occurredAt: $event->occurredAt(),
        );
    }

    public function markSent(\DateTimeImmutable $now): void
    {
        $this->status = OutboxStatus::Sent;
        $this->sentAt = $now;
        $this->lastError = null;
    }

    public function markFailed(string $error): void
    {
        ++$this->attempts;

        // Jeden neúspěch ještě není konec. Do failed řádek propadne až
        // po vyčerpání pokusů; do té doby čeká, aby relay nepálil
        // všech pět pokusů během jediné vteřiny.
        $this->status = $this->attempts >= self::MAX_ATTEMPTS
            ? OutboxStatus::Failed
            : OutboxStatus::Pending;

        $this->availableAt = new \DateTimeImmutable(
            sprintf('+%d seconds', 2 ** $this->attempts),
        );
        $this->lastError = $error;
    }

    /** Relay bere jen řádky, kterým už uplynul odklad. */
    public function isAvailableAt(\DateTimeImmutable $now): bool
    {
        return $this->status === OutboxStatus::Pending
            && $this->availableAt <= $now;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function status(): OutboxStatus
    {
        return $this->status;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }
}
