<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Application\Command;

use App\Chapter07_Sagas\SharedKernel\Application\Command\CompensatableCommand;

final readonly class ChargeCustomer implements CompensatableCommand
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $amountCents,
    ) {}

    public function compensation(): RefundCustomer
    {
        // Zde je vidět mez vzoru: příkaz identifikátor transakce nezná,
        // protože ho teprve vytvoří. Objednávkový proces proto kompenzaci
        // řídí z Process Manageru, který si transactionId uloží do kontextu.
        return new RefundCustomer(
            orderId: $this->orderId,
            customerId: $this->customerId,
            transactionId: '',
            amountCents: $this->amountCents,
            reason: 'Saga compensation',
        );
    }
}
