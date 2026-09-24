<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Application\Handler;

use App\Chapter07_Sagas\Shipping\Application\Command\CreateShipment;
use App\Chapter07_Sagas\Shipping\Domain\Event\ShipmentCreated;
use App\Chapter07_Sagas\Shipping\Domain\ShippingService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateShipmentHandler
{
    public function __construct(
        private ShippingService $shipping,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CreateShipment $command): void
    {
        // Krok za pivotem (rezervací skladu): z doménových důvodů
        // neselhává, technické chyby řeší retry transportu.
        $shipmentId = $this->shipping->create($command->orderId);

        $this->eventBus->dispatch(new ShipmentCreated(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
            shipmentId: $shipmentId,
        ));
    }
}
