<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Warehouse\Application\Handler;

use App\Chapter07_Sagas\Warehouse\Application\Command\ReserveStock;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReservationFailed;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReserved;
use App\Chapter07_Sagas\Warehouse\Domain\StockService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ReserveStockHandler
{
    public function __construct(
        private StockService $stock,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(ReserveStock $command): void
    {
        try {
            $this->stock->reserve($command->orderId);
        } catch (\RuntimeException $e) {
            $this->eventBus->dispatch(new StockReservationFailed(
                eventId: Uuid::v7(),
                orderId: $command->orderId,
                failureReason: $e->getMessage(),
            ));

            return;
        }

        $this->eventBus->dispatch(new StockReserved(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
        ));
    }
}
