<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain\Events;

use App\Shared\Domain\DomainEvent;

final readonly class ShipmentFailed implements DomainEvent
{
    public function __construct(
        public string $sagaId,
        public string $orderId,
        public string $reason,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
