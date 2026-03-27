<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Command;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public string $sagaId,
        public string $orderId,
        public int $amount,
        public bool $shouldSucceed,
    ) {
    }
}
