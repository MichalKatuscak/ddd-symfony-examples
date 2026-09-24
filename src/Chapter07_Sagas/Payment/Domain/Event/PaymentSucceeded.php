<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Domain\Event;

use Symfony\Component\Uid\Uuid;

// Každá kroková událost nese vlastní identitu. Bez ní nejde zapnout
// idempotenci ságy – guard se nemá čeho chytit a opakované doručení
// provede přechod podruhé.
final readonly class PaymentSucceeded
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $transactionId = '',
    ) {}
}
