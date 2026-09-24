<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Warehouse\Application\Command;

final readonly class ReserveStock
{
    public function __construct(public string $orderId) {}
}
