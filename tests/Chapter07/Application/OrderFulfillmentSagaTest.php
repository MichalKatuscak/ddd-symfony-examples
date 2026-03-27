<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Application;

use App\Chapter07_Sagas\Application\Command\CompensateCommand;
use App\Chapter07_Sagas\Application\Command\ProcessPaymentCommand;
use App\Chapter07_Sagas\Application\Command\ReserveStockCommand;
use App\Chapter07_Sagas\Application\Command\ShipOrderCommand;
use App\Chapter07_Sagas\Application\Command\StartSagaCommand;
use App\Chapter07_Sagas\Application\Handler\CompensateHandler;
use App\Chapter07_Sagas\Application\Handler\ProcessPaymentHandler;
use App\Chapter07_Sagas\Application\Handler\ReserveStockHandler;
use App\Chapter07_Sagas\Application\Handler\ShipOrderHandler;
use App\Chapter07_Sagas\Application\SagaOrchestrator;
use App\Chapter07_Sagas\Domain\SagaState;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class OrderFulfillmentSagaTest extends TestCase
{
    private InMemorySagaRepository $repository;
    private SagaOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->repository = new InMemorySagaRepository();

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                ReserveStockCommand::class => [new ReserveStockHandler($this->repository)],
                ProcessPaymentCommand::class => [new ProcessPaymentHandler($this->repository)],
                ShipOrderCommand::class => [new ShipOrderHandler($this->repository)],
                CompensateCommand::class => [new CompensateHandler($this->repository)],
            ])),
        ]);

        $this->orchestrator = new SagaOrchestrator($this->repository, $bus);
    }

    public function test_happy_path_completes_all_steps(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand(
            orderId: 'order-1',
            amount: 1500,
            stockAvailable: true,
            paymentSuccess: true,
            shipmentSuccess: true,
        ));

        $this->assertSame(SagaState::Completed, $saga->state());

        $stepNames = array_map(fn ($s) => $s->name, $saga->steps());
        $this->assertContains('StartSaga', $stepNames);
        $this->assertContains('ReserveStock', $stepNames);
        $this->assertContains('ProcessPayment', $stepNames);
        $this->assertContains('ShipOrder', $stepNames);

        foreach ($saga->steps() as $step) {
            $this->assertSame('ok', $step->status);
        }
    }

    public function test_stock_failure_fails_immediately(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand(
            orderId: 'order-2',
            amount: 1500,
            stockAvailable: false,
            paymentSuccess: true,
            shipmentSuccess: true,
        ));

        $this->assertSame(SagaState::Failed, $saga->state());

        $stepNames = array_map(fn ($s) => $s->name, $saga->steps());
        $this->assertContains('ReserveStock', $stepNames);
        $this->assertNotContains('ProcessPayment', $stepNames);
        $this->assertNotContains('ShipOrder', $stepNames);
    }

    public function test_payment_failure_compensates_stock(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand(
            orderId: 'order-3',
            amount: 1500,
            stockAvailable: true,
            paymentSuccess: false,
            shipmentSuccess: true,
        ));

        $this->assertSame(SagaState::Failed, $saga->state());

        $stepNames = array_map(fn ($s) => $s->name, $saga->steps());
        $this->assertContains('ReserveStock', $stepNames);
        $this->assertContains('ProcessPayment', $stepNames);
        $this->assertContains('CompensateStock', $stepNames);
        $this->assertNotContains('ShipOrder', $stepNames);
    }

    public function test_shipment_failure_compensates_payment_and_stock(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand(
            orderId: 'order-4',
            amount: 1500,
            stockAvailable: true,
            paymentSuccess: true,
            shipmentSuccess: false,
        ));

        $this->assertSame(SagaState::Failed, $saga->state());

        $stepNames = array_map(fn ($s) => $s->name, $saga->steps());
        $this->assertContains('ReserveStock', $stepNames);
        $this->assertContains('ProcessPayment', $stepNames);
        $this->assertContains('ShipOrder', $stepNames);
        $this->assertContains('CompensatePayment', $stepNames);
        $this->assertContains('CompensateStock', $stepNames);
    }
}
