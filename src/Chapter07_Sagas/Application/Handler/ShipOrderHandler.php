<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Handler;

use App\Chapter07_Sagas\Application\Command\ShipOrderCommand;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ShipOrderHandler
{
    public function __construct(
        private InMemorySagaRepository $repository,
    ) {
    }

    public function __invoke(ShipOrderCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);

        if ($command->shouldSucceed) {
            $saga->handleShipmentCompleted();
        } else {
            $saga->handleShipmentFailed('Shipment carrier unavailable');
        }

        $this->repository->save($saga);
    }
}
