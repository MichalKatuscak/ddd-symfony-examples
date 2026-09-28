<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Handler;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrder;
use App\Chapter07_Sagas\Ordering\Application\Command\CheckSagaTimeout;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaRepository;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use App\Chapter07_Sagas\SharedKernel\Domain\SystemActor;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CheckSagaTimeoutHandler
{
    public function __construct(
        private OrderSagaRepository $sagaRepository,
        #[Target('messenger.bus.command')]
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(CheckSagaTimeout $command): void
    {
        $state = $this->sagaRepository->findByCorrelationId($command->orderId);

        // Sága se od posledního kroku posunula, nebo pro tuto objednávku
        // vůbec neběží – timeout v obou případech neplatí.
        if ($state === null || $state->status()->value !== $command->expectedStatus) {
            return;
        }

        match (OrderSagaStatus::from($command->expectedStatus)) {
            OrderSagaStatus::AwaitingPayment => $this->failWithoutCompensation($state),
            OrderSagaStatus::AwaitingStockReservation => $this->compensatePayment($state),
            default => null,
        };
    }

    private function failWithoutCompensation(OrderSaga $state): void
    {
        // Platba nikdy neproběhla – není co kompenzovat. Zámek na objednávce
        // uvolní CancelOrderHandler, protože příkaz přichází pod systémovou
        // identitou.
        $state->transitionTo(OrderSagaStatus::Failed);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new CancelOrder(
            orderId: OrderId::fromString($state->correlationId()),
            reason: 'Payment timeout',
            actorId: CustomerId::fromString(SystemActor::ID),
        ));
    }

    private function compensatePayment(OrderSaga $state): void
    {
        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $state->correlationId(),
            customerId: $state->context()['customerId'],
            transactionId: $state->context()['transactionId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Timeout: stock reservation not received',
        ));

        // CancelOrder zde nedispatchujeme. Objednávku zruší
        // onRefundSucceeded až po potvrzení refundu.
    }
}
