<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Command;

final readonly class CheckSagaTimeout
{
    public function __construct(
        public string $orderId,
        public string $expectedStatus,
    ) {}
}
