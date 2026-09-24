<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Application\Command;

final readonly class RefundCustomer
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        // Bez identifikátoru transakce nemá brána co vrátit. Sága si ho
        // proto z PaymentSucceeded ukládá do kontextu.
        public string $transactionId,
        public int $amountCents,
        public string $reason,
    ) {}
}
