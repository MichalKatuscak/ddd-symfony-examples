<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain\Events;

use App\Shared\Domain\DomainEvent;

final readonly class SagaCompleted implements DomainEvent
{
    public function __construct(
        public string $sagaId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
