<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Command;

final readonly class StartSagaCommand
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public bool $stockAvailable = true,
        public bool $paymentSuccess = true,
        public bool $shipmentSuccess = true,
    ) {
    }
}
