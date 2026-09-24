<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Integration;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrderCommand;
use App\Chapter07_Sagas\Ordering\Application\Command\CheckSagaTimeout;
use App\Chapter07_Sagas\Ordering\Application\Command\MarkOrderPaid;
use App\Chapter07_Sagas\Ordering\Application\Command\ReleaseOrderLock;
use App\Chapter07_Sagas\Ordering\Application\Command\ShipOrder;
use App\Chapter07_Sagas\Ordering\Application\Handler\CancelOrderHandler;
use App\Chapter07_Sagas\Ordering\Application\Handler\CheckSagaTimeoutHandler;
use App\Chapter07_Sagas\Ordering\Application\Handler\MarkOrderPaidHandler;
use App\Chapter07_Sagas\Ordering\Application\Handler\ReleaseOrderLockHandler;
use App\Chapter07_Sagas\Ordering\Application\Handler\ShipOrderHandler;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderProcessManager;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use App\Chapter07_Sagas\Ordering\Infrastructure\Saga\InMemoryOrderSagaRepository;
use App\Chapter07_Sagas\Payment\Application\Command\ChargeCustomer;
use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use App\Chapter07_Sagas\Payment\Application\Handler\ChargeCustomerHandler;
use App\Chapter07_Sagas\Payment\Application\Handler\RefundCustomerHandler;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentSucceeded;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundSucceeded;
use App\Chapter07_Sagas\Payment\Infrastructure\InMemoryPaymentGateway;
use App\Chapter07_Sagas\Shipping\Application\Command\CancelShipment;
use App\Chapter07_Sagas\Shipping\Application\Command\CreateShipment;
use App\Chapter07_Sagas\Shipping\Application\Handler\CancelShipmentHandler;
use App\Chapter07_Sagas\Shipping\Application\Handler\CreateShipmentHandler;
use App\Chapter07_Sagas\Shipping\Domain\Event\ShipmentCreated;
use App\Chapter07_Sagas\Shipping\Infrastructure\InMemoryShippingService;
use App\Chapter07_Sagas\Warehouse\Application\Command\ReleaseStock;
use App\Chapter07_Sagas\Warehouse\Application\Command\ReserveStock;
use App\Chapter07_Sagas\Warehouse\Application\Handler\ReleaseStockHandler;
use App\Chapter07_Sagas\Warehouse\Application\Handler\ReserveStockHandler;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReservationFailed;
use App\Chapter07_Sagas\Warehouse\Domain\Event\StockReserved;
use App\Chapter07_Sagas\Warehouse\Infrastructure\InMemoryStockService;
use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderCancelled;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter11_OutboxPattern\Ordering\Infrastructure\InMemoryOrderRepository;
use App\Chapter11_OutboxPattern\Outbox\Application\DomainEventSerializer;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxMessageFactory;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InMemoryOutboxRepository;
use App\Tests\Chapter11\TestSerializer;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Uid\Uuid;

/**
 * Celý proces přes skutečné sběrnice Messengeru, synchronně jako na stránce
 * ukázky. Test kontroluje stav ságy i stav agregátu – sága může doběhnout
 * do Completed a objednávka přesto zůstat rozpracovaná, když chybí příkaz.
 */
final class OrderSagaFlowTest extends TestCase
{
    private InMemoryOrderRepository $orders;
    private InMemoryOrderSagaRepository $sagas;
    private InMemoryPaymentGateway $gateway;
    private InMemoryStockService $stock;
    private MessageBusInterface $eventBus;
    private PlaceOrderHandler $placeOrder;
    private InMemoryOutboxRepository $outbox;
    private OutboxMessageFactory $outboxFactory;

    protected function setUp(): void
    {
        $this->orders = new InMemoryOrderRepository();
        $this->sagas = new InMemoryOrderSagaRepository();
        $this->gateway = new InMemoryPaymentGateway();
        $this->stock = new InMemoryStockService();
        $shipping = new InMemoryShippingService();

        // Handlery potřebují sběrnice a sběrnice handlery; zpoždění řeší odkaz.
        $commandBus = new DeferredBus();
        $eventBus = new DeferredBus();

        $processManager = new OrderProcessManager($commandBus, $this->sagas, $this->createStub(ManagerRegistry::class));

        $commandBus->inner = new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            ChargeCustomer::class => [new ChargeCustomerHandler($this->gateway, $eventBus)],
            RefundCustomer::class => [new RefundCustomerHandler($this->gateway, $eventBus)],
            ReserveStock::class => [new ReserveStockHandler($this->stock, $eventBus)],
            ReleaseStock::class => [new ReleaseStockHandler($this->stock)],
            CreateShipment::class => [new CreateShipmentHandler($shipping, $eventBus)],
            CancelShipment::class => [new CancelShipmentHandler($shipping)],
            MarkOrderPaid::class => [new MarkOrderPaidHandler($this->orders, $eventBus)],
            ShipOrder::class => [new ShipOrderHandler($this->orders, $eventBus)],
            CancelOrderCommand::class => [new CancelOrderHandler($this->orders, $eventBus)],
            ReleaseOrderLock::class => [new ReleaseOrderLockHandler($this->orders)],
            CheckSagaTimeout::class => [new CheckSagaTimeoutHandler($this->sagas, $commandBus)],
        ]))]);

        $sagaEvents = [
            OrderPlacedIntegrationEvent::class, PaymentSucceeded::class, PaymentFailed::class,
            StockReserved::class, StockReservationFailed::class, ShipmentCreated::class,
            RefundSucceeded::class, RefundFailed::class, OrderCancelled::class,
        ];
        // event.bus má allow_no_handlers: OrderPaid ani OrderShipped nikdo neodebírá.
        $eventBus->inner = new MessageBus([new HandleMessageMiddleware(
            new HandlersLocator(array_fill_keys($sagaEvents, [$processManager])),
            allowNoHandlers: true,
        )]);
        $this->eventBus = $eventBus;

        $serializer = TestSerializer::create();
        $this->outbox = new InMemoryOutboxRepository();
        $this->outboxFactory = new OutboxMessageFactory($serializer);
        $this->placeOrder = new PlaceOrderHandler($this->orders, $this->outbox, new DomainEventSerializer($serializer));
    }

    public function test_happy_path_ships_order_and_releases_lock(): void
    {
        $orderId = $this->placeOrderAndRelay();

        $saga = $this->sagas->findByCorrelationId($orderId);
        self::assertSame(OrderSagaStatus::Completed, $saga?->status());
        self::assertSame(['payment_charged', 'stock_reserved', 'shipment_created'], $saga->context()['completedSteps']);

        $order = $this->orders->get(OrderId::fromString($orderId));
        self::assertSame(OrderStatus::Shipped, $order->status);
        self::assertFalse($order->isLockedBySaga());
        self::assertTrue($this->stock->isReserved($orderId));
    }

    public function test_payment_failure_cancels_order_without_compensation(): void
    {
        $this->gateway->failAlways();

        $orderId = $this->placeOrderAndRelay();

        self::assertSame(OrderSagaStatus::Failed, $this->sagas->findByCorrelationId($orderId)?->status());
        $order = $this->orders->get(OrderId::fromString($orderId));
        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertFalse($order->isLockedBySaga());
    }

    public function test_stock_failure_refunds_payment_then_cancels_paid_order(): void
    {
        $this->stock->failAlways();

        $orderId = $this->placeOrderAndRelay();

        $saga = $this->sagas->findByCorrelationId($orderId);
        self::assertSame(OrderSagaStatus::Failed, $saga?->status());
        self::assertSame(['payment_charged'], $saga->context()['completedSteps']);

        // Paid → Cancelled je povolená hrana; zámek uvolnil CancelOrderHandler.
        $order = $this->orders->get(OrderId::fromString($orderId));
        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertFalse($order->isLockedBySaga());
    }

    private function placeOrderAndRelay(): string
    {
        $orderId = ($this->placeOrder)(new PlaceOrder((string) Uuid::v7(), [
            ['productId' => (string) Uuid::v7(), 'quantity' => 2, 'unitPriceInCents' => 750_00],
        ]));

        foreach ($this->outbox->fetchPending() as $row) {
            $this->eventBus->dispatch($this->outboxFactory->reconstitute($row));
            $this->outbox->markSent($row->id);
        }

        return $orderId->value;
    }
}
