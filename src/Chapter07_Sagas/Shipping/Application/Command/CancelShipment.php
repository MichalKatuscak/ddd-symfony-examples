<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Application\Command;

final readonly class CancelShipment
{
    // Zásilka se ruší podle vlastní identity, ne podle objednávky. Sága
    // si shipmentId ukládá z ShipmentCreated – jinde ho v tu chvíli
    // nemá kdo znát.
    public function __construct(
        public string $orderId,
        public string $shipmentId,
    ) {}
}
