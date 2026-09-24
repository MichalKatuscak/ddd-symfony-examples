<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Domain;

interface PaymentGateway
{
    /**
     * @return string identifikátor transakce
     * @throws \RuntimeException když platba neprojde
     */
    public function charge(string $customerId, int $amountCents): string;

    /**
     * @return string identifikátor refundu
     * @throws \RuntimeException když refund neprojde
     */
    public function refund(string $transactionId, int $amountCents): string;
}
