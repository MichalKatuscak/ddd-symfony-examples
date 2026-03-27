<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application;

use App\Chapter07_Sagas\Application\Command\CompensateCommand;
use App\Chapter07_Sagas\Application\Command\ProcessPaymentCommand;
use App\Chapter07_Sagas\Application\Command\ReserveStockCommand;
use App\Chapter07_Sagas\Application\Command\ShipOrderCommand;
use App\Chapter07_Sagas\Application\Command\StartSagaCommand;
use App\Chapter07_Sagas\Domain\OrderFulfillmentSaga;
use App\Chapter07_Sagas\Domain\SagaState;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

final readonly class SagaOrchestrator
{
    public function __construct(
        private InMemorySagaRepository $repository,
        private MessageBusInterface $bus,
    ) {
    }

    public function start(StartSagaCommand $command): OrderFulfillmentSaga
    {
        $sagaId = Uuid::v4()->toRfc4122();

        $saga = OrderFulfillmentSaga::start($sagaId, $command->orderId, $command->amount);
        $this->repository->save($saga);

        // Step 1: Reserve stock
        $this->bus->dispatch(new ReserveStockCommand(
            sagaId: $sagaId,
            orderId: $command->orderId,
            shouldSucceed: $command->stockAvailable,
        ));

        $saga = $this->repository->findById($sagaId);
        if ($saga->state() === SagaState::Failed) {
            return $saga;
        }

        // Step 2: Process payment
        $this->bus->dispatch(new ProcessPaymentCommand(
            sagaId: $sagaId,
            orderId: $command->orderId,
            amount: $command->amount,
            shouldSucceed: $command->paymentSuccess,
        ));

        $saga = $this->repository->findById($sagaId);
        if ($saga->state() === SagaState::Compensating) {
            $this->bus->dispatch(new CompensateCommand(
                sagaId: $sagaId,
                failedStep: 'ProcessPayment',
            ));

            return $this->repository->findById($sagaId);
        }

        // Step 3: Ship order
        $this->bus->dispatch(new ShipOrderCommand(
            sagaId: $sagaId,
            orderId: $command->orderId,
            shouldSucceed: $command->shipmentSuccess,
        ));

        $saga = $this->repository->findById($sagaId);
        if ($saga->state() === SagaState::Compensating) {
            $this->bus->dispatch(new CompensateCommand(
                sagaId: $sagaId,
                failedStep: 'ShipOrder',
            ));

            return $this->repository->findById($sagaId);
        }

        return $saga;
    }
}
