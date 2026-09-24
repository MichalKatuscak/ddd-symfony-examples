<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Application\Command;

final readonly class CreateShipment
{
    public function __construct(public string $orderId) {}
}
