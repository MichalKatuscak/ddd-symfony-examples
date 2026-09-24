<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Warehouse\Domain;

interface StockService
{
    /** @throws \RuntimeException když zboží není skladem */
    public function reserve(string $orderId): void;

    public function release(string $orderId): void;
}
