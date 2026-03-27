<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

final readonly class SagaStep
{
    public function __construct(
        public string $name,
        public string $status,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
