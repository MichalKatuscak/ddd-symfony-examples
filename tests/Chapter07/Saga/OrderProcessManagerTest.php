<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Saga;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrderCommand;
use App\Chapter07_Sagas\Ordering\Application\Command\CheckSagaTimeout;
use App\Chapter07_Sagas\Ordering\Application\Command\MarkOrderPaid;
use App\Chapter07_Sagas\Ordering\Application\Command\ReleaseOrderLock;
use App\Chapter07_Sagas\Ordering\Application\Command\ShipOrder;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderProcessManager;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use App\Chapter07_Sagas\Ordering\Infrastructure\Saga\InMemoryOrderSagaRepository;
use App\Chapter07_Sagas\Payment\Application\Command\ChargeCustomer;
use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentSucceeded;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundSucceeded;
use App\Chapter07_Sagas\SharedKernel\Domain\SystemActor;
use App\Chapter07_Sagas\Shipping\Application\Command\CancelShipment;
use App\Chapter07_Sagas\Shipping\Application\Command\CreateShipment;
use App\Chapter07_Sagas\Shipping\Domain\Event\ShipmentCreated;
use App\Chapter07_Sagas\Warehouse\Application\Command\ReleaseStock;
use App\Chapter07_Sagas\Warehouse\Application\Command\ReserveStock;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReservationFailed;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReserved;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderCancelled;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit testy stavového automatu podle 14.12: spy místo sběrnice,
 * in-memory repozitář místo databáze.
 */
final class OrderProcessManagerTest extends TestCase
{
    private const ORDER_ID = '01a07424-28ff-7c31-9d40-6f2a1c8e5b03';
    private const CUSTOMER_ID = '01a07424-28ff-7c31-9d40-6f2a1c8e5b04';

    private SpyBus $commandBus;
    private InMemoryOrderSagaRepository $repository;
    private OrderProcessManager $saga;

    protected function setUp(): void
    {
        $this->commandBus = new SpyBus();
        $this->repository = new InMemoryOrderSagaRepository();
        $this->saga = new OrderProcessManager(
            $this->commandBus,
            $this->repository,
            $this->createStub(ManagerRegistry::class),
        );
    }

    public function test_order_placed_initiates_payment(): void
    {
        ($this->saga)($this->orderPlaced());

        self::assertCount(1, $this->steps());
        self::assertInstanceOf(ChargeCustomer::class, $this->steps()[0]);
        self::assertSame(10000, $this->steps()[0]->amountCents);
        self::assertSame(OrderSagaStatus::AwaitingPayment, $this->state()->status());
    }

    public function test_order_placed_schedules_payment_timeout(): void
    {
        ($this->saga)($this->orderPlaced());

        $timeouts = $this->timeouts();
        self::assertCount(1, $timeouts);
        self::assertSame(OrderSagaStatus::AwaitingPayment->value, $timeouts[0]->expectedStatus);
    }

    public function test_duplicate_order_placed_does_not_charge_twice(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)($this->orderPlaced()); // souběžné doručení téže události

        $charges = array_filter($this->steps(), static fn (object $c): bool => $c instanceof ChargeCustomer);
        self::assertCount(1, $charges);
    }

    public function test_payment_succeeded_marks_order_paid_and_reserves_stock(): void
    {
        ($this->saga)($this->orderPlaced());
        $this->commandBus->messages = [];

        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID, transactionId: 'tx-1'));

        self::assertCount(2, $this->steps());
        self::assertInstanceOf(MarkOrderPaid::class, $this->steps()[0]);
        self::assertInstanceOf(ReserveStock::class, $this->steps()[1]);
        self::assertSame(OrderSagaStatus::AwaitingStockReservation, $this->state()->status());
        self::assertSame('tx-1', $this->state()->context()['transactionId']);
        self::assertSame(['payment_charged'], $this->state()->context()['completedSteps']);
        self::assertSame(OrderSagaStatus::AwaitingStockReservation->value, $this->timeouts()[0]->expectedStatus);
    }

    public function test_redelivered_payment_succeeded_is_ignored(): void
    {
        ($this->saga)($this->orderPlaced());
        $event = new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID, transactionId: 'tx-1');
        ($this->saga)($event);
        $this->commandBus->messages = [];

        ($this->saga)($event); // tatáž událost podruhé

        self::assertSame([], $this->steps());
        self::assertSame(['payment_charged'], $this->state()->context()['completedSteps']);
    }

    public function test_redelivered_stock_reserved_creates_single_shipment(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID, transactionId: 'tx-1'));
        $event = new StockReserved(eventId: Uuid::v7(), orderId: self::ORDER_ID);
        ($this->saga)($event);
        $this->commandBus->messages = [];

        ($this->saga)($event); // tatáž událost podruhé

        // Bez guardu by vznikla druhá zásilka a kompenzace by zrušila jen jednu.
        self::assertSame([], $this->steps());
        self::assertSame(['payment_charged', 'stock_reserved'], $this->state()->context()['completedSteps']);
    }

    public function test_shipment_created_completes_saga_releases_lock_and_ships_order(): void
    {
        $this->advanceToAwaitingShipment();
        $this->commandBus->messages = [];

        ($this->saga)(new ShipmentCreated(eventId: Uuid::v7(), orderId: self::ORDER_ID, shipmentId: 'sh-1'));

        self::assertSame(OrderSagaStatus::Completed, $this->state()->status());
        self::assertInstanceOf(ReleaseOrderLock::class, $this->steps()[0]);
        self::assertInstanceOf(ShipOrder::class, $this->steps()[1]);
        self::assertSame('sh-1', $this->steps()[1]->shipmentId);
    }

    public function test_payment_failed_fails_saga_and_cancels_order_as_system(): void
    {
        ($this->saga)($this->orderPlaced());
        $this->commandBus->messages = [];

        ($this->saga)(new PaymentFailed(eventId: Uuid::v7(), orderId: self::ORDER_ID, failureReason: 'Insufficient funds'));

        self::assertSame(OrderSagaStatus::Failed, $this->state()->status());
        $cancel = $this->steps()[1];
        self::assertInstanceOf(CancelOrderCommand::class, $cancel);
        self::assertSame(SystemActor::ID, $cancel->actorId->value);
    }

    public function test_late_event_does_not_revive_finished_saga(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)(new PaymentFailed(eventId: Uuid::v7(), orderId: self::ORDER_ID, failureReason: 'Insufficient funds'));
        $this->commandBus->messages = [];

        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID));

        self::assertSame([], $this->steps());
        self::assertSame(OrderSagaStatus::Failed, $this->state()->status());
    }

    public function test_stock_failure_refunds_payment_and_waits_for_confirmation(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID, transactionId: 'tx-1'));
        $this->commandBus->messages = [];

        ($this->saga)(new StockReservationFailed(eventId: Uuid::v7(), orderId: self::ORDER_ID, failureReason: 'Není skladem'));

        self::assertSame(OrderSagaStatus::Compensating, $this->state()->status());
        self::assertCount(1, $this->steps());
        self::assertInstanceOf(RefundCustomer::class, $this->steps()[0]);
        self::assertSame('tx-1', $this->steps()[0]->transactionId);

        // Do Failed sága přejde až po potvrzení refundu.
        ($this->saga)(new RefundSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID));
        self::assertSame(OrderSagaStatus::Failed, $this->state()->status());
        self::assertInstanceOf(CancelOrderCommand::class, $this->steps()[1]);
    }

    public function test_cancellation_compensates_completed_steps_in_reverse_order(): void
    {
        $this->advanceToAwaitingShipment();
        $this->commandBus->messages = [];

        ($this->saga)(new OrderCancelled(
            OrderId::fromString(self::ORDER_ID),
            CustomerId::fromString(self::CUSTOMER_ID),
            'Zákazník si to rozmyslel',
            new \DateTimeImmutable(),
        ));

        self::assertSame(OrderSagaStatus::Compensating, $this->state()->status());
        self::assertSame(
            [ReleaseStock::class, RefundCustomer::class],
            array_map(static fn (object $c): string => $c::class, $this->steps()),
        );
    }

    public function test_late_stock_and_shipment_during_compensation_are_undone(): void
    {
        $this->advanceToAwaitingShipment();
        ($this->saga)(new OrderCancelled(
            OrderId::fromString(self::ORDER_ID),
            CustomerId::fromString(self::CUSTOMER_ID),
            'Storno',
            new \DateTimeImmutable(),
        ));
        $this->commandBus->messages = [];

        ($this->saga)(new StockReserved(eventId: Uuid::v7(), orderId: self::ORDER_ID));
        ($this->saga)(new ShipmentCreated(eventId: Uuid::v7(), orderId: self::ORDER_ID, shipmentId: 'sh-late'));

        self::assertInstanceOf(ReleaseStock::class, $this->steps()[0]);
        self::assertInstanceOf(CancelShipment::class, $this->steps()[1]);
        self::assertSame('sh-late', $this->steps()[1]->shipmentId);
        self::assertSame(OrderSagaStatus::Compensating, $this->state()->status());
    }

    private function advanceToAwaitingShipment(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID, transactionId: 'tx-1'));
        ($this->saga)(new StockReserved(eventId: Uuid::v7(), orderId: self::ORDER_ID));

        self::assertSame(OrderSagaStatus::AwaitingShipment, $this->state()->status());
        self::assertInstanceOf(CreateShipment::class, $this->steps()[array_key_last($this->steps())]);
    }

    private function orderPlaced(): OrderPlacedIntegrationEvent
    {
        return new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: self::ORDER_ID,
            customerId: self::CUSTOMER_ID,
            items: [],
            totalAmountCents: 10000,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private function state(): OrderSaga
    {
        $state = $this->repository->findByCorrelationId(self::ORDER_ID);
        self::assertNotNull($state);

        return $state;
    }

    /**
     * Kroky procesu bez odložených hlídačů – jinak by se počty rozešly
     * při každém přidaném timeoutu.
     *
     * @return list<object>
     */
    private function steps(): array
    {
        return array_values(array_filter(
            $this->commandBus->messages,
            static fn (object $c): bool => !$c instanceof CheckSagaTimeout,
        ));
    }

    /** @return list<CheckSagaTimeout> */
    private function timeouts(): array
    {
        return array_values(array_filter(
            $this->commandBus->messages,
            static fn (object $c): bool => $c instanceof CheckSagaTimeout,
        ));
    }
}
