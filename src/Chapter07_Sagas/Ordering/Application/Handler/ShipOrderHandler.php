<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Handler;

use App\Chapter07_Sagas\Ordering\Application\Command\ShipOrder;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Shipping\Domain\ValueObject\ShipmentId;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ShipOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(ShipOrder $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));
        // ship() má stejnou idempotentní větev jako markPaid().
        $order->ship(ShipmentId::fromString($command->shipmentId));
        $this->orders->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
