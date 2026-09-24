<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Infrastructure;

use App\Chapter07_Sagas\Payment\Domain\PaymentGateway;
use Symfony\Component\Uid\Uuid;

/**
 * Platební brána v paměti. Kniha přepínač plní z proměnné prostředí
 * PAYMENT_FAILS; ukázka ho nastavuje z formuláře, aby šly všechny větve
 * vyzkoušet bez restartu serveru.
 */
final class InMemoryPaymentGateway implements PaymentGateway
{
    public function __construct(
        private bool $alwaysFails = false,
    ) {}

    public function failAlways(bool $fails = true): void
    {
        $this->alwaysFails = $fails;
    }

    public function charge(string $customerId, int $amountCents): string
    {
        if ($this->alwaysFails) {
            throw new \RuntimeException('Platba zamítnuta.');
        }

        return (string) Uuid::v7();
    }

    public function refund(string $transactionId, int $amountCents): string
    {
        return (string) Uuid::v7();
    }
}
