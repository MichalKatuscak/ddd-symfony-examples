<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Handler;

use App\Chapter07_Sagas\Application\Command\CompensateCommand;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CompensateHandler
{
    public function __construct(
        private InMemorySagaRepository $repository,
    ) {
    }

    public function __invoke(CompensateCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);

        match ($command->failedStep) {
            'ShipOrder' => $this->compensateShipmentFailure($saga),
            'ProcessPayment' => $this->compensatePaymentFailure($saga),
            default => null,
        };

        $saga->markFailed('Compensation completed after ' . $command->failedStep . ' failure');
        $this->repository->save($saga);
    }

    private function compensateShipmentFailure(\App\Chapter07_Sagas\Domain\OrderFulfillmentSaga $saga): void
    {
        $saga->compensatePayment();
        $saga->compensateStock();
    }

    private function compensatePaymentFailure(\App\Chapter07_Sagas\Domain\OrderFulfillmentSaga $saga): void
    {
        $saga->compensateStock();
    }
}
