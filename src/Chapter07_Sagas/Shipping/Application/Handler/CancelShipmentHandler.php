<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Application\Handler;

use App\Chapter07_Sagas\Shipping\Application\Command\CancelShipment;
use App\Chapter07_Sagas\Shipping\Domain\ShippingService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CancelShipmentHandler
{
    public function __construct(private ShippingService $shipping) {}

    public function __invoke(CancelShipment $command): void
    {
        // Nevydává nic. Kdyby vydal ShipmentCreated, Process Manager by na
        // ni ve stavu Compensating odpověděl dalším CancelShipment a vznikla
        // by nekonečná smyčka příkazu a události.
        $this->shipping->cancel($command->shipmentId);
    }
}
