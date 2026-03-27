<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Application\Handler;

use App\Chapter07_Sagas\Application\Command\ProcessPaymentCommand;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessPaymentHandler
{
    public function __construct(
        private InMemorySagaRepository $repository,
    ) {
    }

    public function __invoke(ProcessPaymentCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);

        if ($command->shouldSucceed) {
            $saga->handlePaymentProcessed();
        } else {
            $saga->handlePaymentFailed('Payment declined');
        }

        $this->repository->save($saga);
    }
}
