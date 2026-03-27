<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Handler;

use App\Chapter07_Sagas\Application\Command\ReserveStockCommand;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReserveStockHandler
{
    public function __construct(
        private InMemorySagaRepository $repository,
    ) {
    }

    public function __invoke(ReserveStockCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);

        if ($command->shouldSucceed) {
            $saga->handleStockReserved();
        } else {
            $saga->handleStockFailed('Out of stock');
        }

        $this->repository->save($saga);
    }
}
