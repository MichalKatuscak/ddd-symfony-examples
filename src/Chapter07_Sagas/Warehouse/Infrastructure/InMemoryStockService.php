<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Warehouse\Infrastructure;

use App\Chapter07_Sagas\Warehouse\Domain\StockService;

/**
 * Sklad v paměti. Přepínač, který v knize plní proměnná STOCK_FAILS,
 * nastavuje v ukázce formulář.
 */
final class InMemoryStockService implements StockService
{
    /** @var array<string, true> */
    private array $reserved = [];

    public function __construct(private bool $alwaysFails = false) {}

    public function failAlways(bool $fails = true): void
    {
        $this->alwaysFails = $fails;
    }

    public function reserve(string $orderId): void
    {
        if ($this->alwaysFails) {
            throw new \RuntimeException('Out of stock.');
        }

        $this->reserved[$orderId] = true;
    }

    public function release(string $orderId): void
    {
        // Idempotentní: uvolnit neexistující rezervaci není chyba.
        unset($this->reserved[$orderId]);
    }

    public function isReserved(string $orderId): bool
    {
        return isset($this->reserved[$orderId]);
    }
}
