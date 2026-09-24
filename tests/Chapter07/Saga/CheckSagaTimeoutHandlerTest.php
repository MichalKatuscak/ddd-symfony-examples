<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Saga;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrderCommand;
use App\Chapter07_Sagas\Ordering\Application\Command\CheckSagaTimeout;
use App\Chapter07_Sagas\Ordering\Application\Handler\CheckSagaTimeoutHandler;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use App\Chapter07_Sagas\Ordering\Infrastructure\Saga\InMemoryOrderSagaRepository;
use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CheckSagaTimeoutHandlerTest extends TestCase
{
    private SpyBus $commandBus;
    private InMemoryOrderSagaRepository $repository;
    private CheckSagaTimeoutHandler $handler;
    private string $orderId;

    protected function setUp(): void
    {
        $this->commandBus = new SpyBus();
        $this->repository = new InMemoryOrderSagaRepository();
        $this->handler = new CheckSagaTimeoutHandler($this->repository, $this->commandBus);
        $this->orderId = (string) Uuid::v7();
    }

    public function test_payment_timeout_fails_saga_without_compensation(): void
    {
        $this->startSaga(OrderSagaStatus::AwaitingPayment);

        ($this->handler)(new CheckSagaTimeout($this->orderId, OrderSagaStatus::AwaitingPayment->value));

        self::assertSame(OrderSagaStatus::Failed, $this->state()->status());
        self::assertCount(1, $this->commandBus->messages);
        self::assertInstanceOf(CancelOrderCommand::class, $this->commandBus->messages[0]);
    }

    public function test_stock_timeout_refunds_payment(): void
    {
        $this->startSaga(OrderSagaStatus::AwaitingStockReservation, ['transactionId' => 'tx-1']);

        ($this->handler)(new CheckSagaTimeout($this->orderId, OrderSagaStatus::AwaitingStockReservation->value));

        self::assertSame(OrderSagaStatus::Compensating, $this->state()->status());
        self::assertInstanceOf(RefundCustomer::class, $this->commandBus->messages[0]);
        self::assertSame('tx-1', $this->commandBus->messages[0]->transactionId);
    }

    public function test_timeout_for_left_status_is_ignored(): void
    {
        $this->startSaga(OrderSagaStatus::AwaitingShipment);

        // Kontrola naplánovaná pro stav, který sága mezitím opustila.
        ($this->handler)(new CheckSagaTimeout($this->orderId, OrderSagaStatus::AwaitingPayment->value));

        self::assertSame([], $this->commandBus->messages);
        self::assertSame(OrderSagaStatus::AwaitingShipment, $this->state()->status());
    }

    /** @param array<string, mixed> $context */
    private function startSaga(OrderSagaStatus $status, array $context = []): void
    {
        $this->repository->save(OrderSaga::start(
            sagaType: 'order_process',
            correlationId: $this->orderId,
            status: $status,
            context: [
                'customerId' => (string) Uuid::v7(),
                'amountCents' => 1500,
                'completedSteps' => [],
                ...$context,
            ],
        ));
    }

    private function state(): OrderSaga
    {
        $state = $this->repository->findByCorrelationId($this->orderId);
        self::assertNotNull($state);

        return $state;
    }
}
