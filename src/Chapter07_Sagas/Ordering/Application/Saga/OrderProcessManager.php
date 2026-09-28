<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Saga;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrder;
use App\Chapter07_Sagas\Ordering\Application\Command\CheckSagaTimeout;
use App\Chapter07_Sagas\Ordering\Application\Command\MarkOrderPaid;
use App\Chapter07_Sagas\Ordering\Application\Command\ReleaseOrderLock;
use App\Chapter07_Sagas\Ordering\Application\Command\ShipOrder;
use App\Chapter07_Sagas\Payment\Application\Command\ChargeCustomer;
use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentSucceeded;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundFailed;
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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Process Manager koordinující objednávkový proces napříč kontexty:
 * Ordering → Payment → Warehouse → Shipping → Ordering (expedice).
 *
 * Kniha (sekce 14.05) ho píše jako jednu třídu. Ukázka obsahuje i doplňky
 * z dalších sekcí: idempotentní přechody přes applyPaymentSucceeded()
 * a obdobné metody (14.06) a plánování timeoutů (14.08).
 */
#[AsMessageHandler(bus: 'event.bus')]
final class OrderProcessManager
{
    /** Doba čekání pro každý stav, ve kterém sága očekává odpověď (v sekundách). */
    private const TIMEOUTS = [
        'awaiting_payment' => 300,
        'awaiting_stock_reservation' => 30,
    ];

    public function __construct(
        #[Target('messenger.bus.command')]
        private readonly MessageBusInterface $commandBus,
        private readonly OrderSagaRepository $sagaRepository,
        private readonly ManagerRegistry $managerRegistry,
    ) {}

    public function __invoke(
        OrderPlacedIntegrationEvent|PaymentSucceeded|PaymentFailed|StockReserved
        |StockReservationFailed|ShipmentCreated|RefundSucceeded|RefundFailed
        |OrderCancelled $event,
    ): void {
        match (true) {
            $event instanceof OrderPlacedIntegrationEvent => $this->onOrderPlaced($event),
            $event instanceof PaymentSucceeded => $this->onPaymentSucceeded($event),
            $event instanceof PaymentFailed => $this->onPaymentFailed($event),
            $event instanceof StockReserved => $this->onStockReserved($event),
            $event instanceof StockReservationFailed => $this->onStockReservationFailed($event),
            $event instanceof ShipmentCreated => $this->onShipmentCreated($event),
            // Bez těchto dvou větví uvázne sága navždy ve stavu Compensating:
            // event.bus má allow_no_handlers, takže se událost tiše ackne.
            $event instanceof RefundSucceeded => $this->onRefundSucceeded($event),
            $event instanceof RefundFailed => $this->onRefundFailed($event),
            // Objednávku může zrušit i člověk, ne jen kompenzace.
            $event instanceof OrderCancelled => $this->onOrderCancelled($event),
        };
    }

    private function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $state = OrderSaga::start(
            sagaType: 'order_process',
            correlationId: $event->orderId,
            status: OrderSagaStatus::AwaitingPayment,
            context: [
                'customerId' => $event->customerId,
                'amountCents' => $event->totalAmountCents,
                'completedSteps' => [],
            ],
        );

        try {
            $this->sagaRepository->save($state);
        } catch (UniqueConstraintViolationException) {
            // Souběžné doručení téže události. Unikátní index
            // (saga_type, correlation_id) druhý zápis odmítl – sága už
            // běží a druhý command by strhl peníze podruhé.
            //
            // Doctrine po neúspěšném flushi EntityManager zavře, takže
            // samotné spolknutí výjimky nestačí.
            $this->managerRegistry->resetManager();

            return;
        }

        $this->commandBus->dispatch(new ChargeCustomer(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amountCents: $event->totalAmountCents,
        ));

        $this->scheduleTimeout($event->orderId, OrderSagaStatus::AwaitingPayment);
    }

    private function onPaymentSucceeded(PaymentSucceeded $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Opožděná událost nesmí vzkřísit ukončenou ságu. Chybějící sága
        // znamená, že událost patří objednávce mimo tento proces.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        // Guard vrátí false u opakovaného doručení. Bez něj by se
        // MarkOrderPaid i ReserveStock odeslaly znovu a v completedSteps
        // by přibyl druhý „payment_charged“.
        if (!$state->applyPaymentSucceeded((string) $event->eventId)) {
            return;
        }

        $state->updateContext('transactionId', $event->transactionId);

        // Bez tohoto řádku nemá pozdější kompenzace podle čeho poznat, že
        // platba proběhla, a RefundCustomer se nikdy neodešle.
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'payment_charged',
        ]);
        $this->sagaRepository->save($state);

        // Stav agregátu mění příkaz, ne sága.
        $this->commandBus->dispatch(new MarkOrderPaid(orderId: $event->orderId));
        $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));

        $this->scheduleTimeout($event->orderId, OrderSagaStatus::AwaitingStockReservation);
    }

    private function onPaymentFailed(PaymentFailed $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $this->finish($state, OrderSagaStatus::Failed);

        $this->commandBus->dispatch(new CancelOrder(
            orderId: OrderId::fromString($event->orderId),
            reason: 'Platba selhala: ' . $event->failureReason,
            // Sága není člověk. Dostává explicitní systémovou identitu,
            // ne chybějícího aktéra.
            actorId: CustomerId::fromString(SystemActor::ID),
        ));
    }

    private function onStockReserved(StockReserved $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        // Rezervace dorazila až po zahájení kompenzace, takže se rovnou
        // uvolní místo toho, aby sága pokračovala dál.
        if ($state->status() === OrderSagaStatus::Compensating) {
            $this->commandBus->dispatch(new ReleaseStock(orderId: $event->orderId));

            return;
        }

        // Opakované doručení by jinak vyrobilo druhou zásilku.
        if (!$state->applyStockReserved((string) $event->eventId)) {
            return;
        }

        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'stock_reserved',
        ]);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new CreateShipment(orderId: $event->orderId));
    }

    private function onStockReservationFailed(StockReservationFailed $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        // Kompenzace: vrátit platbu. RefundCustomer je asynchronní příkaz –
        // sága zůstává ve stavu Compensating a do Failed přejde až po
        // potvrzení RefundSucceeded.
        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $event->orderId,
            customerId: $state->context()['customerId'],
            transactionId: $state->context()['transactionId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Zboží není skladem',
        ));
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId->value);

        // Vlastní kompenzace ságu takto nevzkřísí: ta už je v Compensating
        // nebo terminálním stavu.
        if ($state === null || $state->status()->isTerminal()
            || $state->status() === OrderSagaStatus::Compensating) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        // Vrací se jen to, co už proběhlo, a v opačném pořadí. Seznam
        // hotových kroků je přesně ten důvod, proč si sága vede stav.
        foreach (array_reverse($state->context()['completedSteps']) as $step) {
            match ($step) {
                'shipment_created' => $this->commandBus->dispatch(new CancelShipment(
                    orderId: $event->orderId->value,
                    shipmentId: $state->context()['shipmentId'],
                )),
                'stock_reserved' => $this->commandBus->dispatch(
                    new ReleaseStock(orderId: $event->orderId->value),
                ),
                'payment_charged' => $this->commandBus->dispatch(new RefundCustomer(
                    orderId: $event->orderId->value,
                    customerId: $state->context()['customerId'],
                    transactionId: $state->context()['transactionId'],
                    amountCents: $state->context()['amountCents'],
                    reason: 'Objednávku zrušil zákazník',
                )),
                default => null,
            };
        }
    }

    private function onRefundSucceeded(RefundSucceeded $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::Failed); // teprve teď je sága uzavřená
        $this->sagaRepository->save($state);

        // Zámek uvolní CancelOrderHandler: příkaz přichází pod systémovou
        // identitou. Order::cancel() je idempotentní, takže nevadí, když
        // objednávku zrušil už zákazník a refund byl jen kompenzací.
        $this->commandBus->dispatch(new CancelOrder(
            orderId: OrderId::fromString($event->orderId),
            reason: 'Proces objednávky selhal, platba vrácena',
            actorId: CustomerId::fromString(SystemActor::ID),
        ));
    }

    private function onRefundFailed(RefundFailed $event): void
    {
        // Sem se řízení dostane až poté, co Messenger vyčerpal retry strategii.
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $state->updateContext('manualInterventionReason', $event->failureReason);
        $this->sagaRepository->save($state);

        // Alert + zařazení do fronty ručních zásahů. Sága zůstává
        // v Compensating, dokud ji operátor neuzavře.
    }

    private function onShipmentCreated(ShipmentCreated $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        // Zásilka mohla vzniknout dřív, než dorazilo storno. Compensating
        // terminální není, proto vlastní větev.
        if ($state->status() === OrderSagaStatus::Compensating) {
            $this->commandBus->dispatch(new CancelShipment(
                orderId: $event->orderId,
                shipmentId: $event->shipmentId,
            ));

            return;
        }

        if (!$state->applyShipmentCreated((string) $event->eventId)) {
            return;
        }

        $state->updateContext('shipmentId', $event->shipmentId);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'shipment_created',
        ]);
        $this->finish($state, OrderSagaStatus::Completed);

        // Zásilka existuje, takže objednávka přechází do Shipped.
        // Potvrzení proběhlo už v továrně při vzniku objednávky.
        $this->commandBus->dispatch(new ShipOrder(
            orderId: $event->orderId,
            shipmentId: $event->shipmentId,
        ));
    }

    /** Zámek na objednávce uvolňuje sága, ať skončí jakkoli. */
    private function finish(OrderSaga $state, OrderSagaStatus $status): void
    {
        $state->transitionTo($status);
        $this->sagaRepository->save($state);

        // Bez tohoto kroku zůstane objednávka zamčená navždy. Ve větvích,
        // které končí stornem, zámek uvolní rovnou CancelOrderHandler.
        $this->commandBus->dispatch(
            new ReleaseOrderLock(orderId: $state->correlationId()),
        );
    }

    private function scheduleTimeout(string $orderId, OrderSagaStatus $status): void
    {
        $seconds = self::TIMEOUTS[$status->value] ?? null;

        if ($seconds === null) {
            return;
        }

        // DelayStamp funguje jen na asynchronním transportu. Ukázka žádný
        // nemá, takže se hlídač zpracuje hned – až poté, co synchronně
        // doběhly všechny kroky vyvolané před ním. Sága už stav opustila
        // a handler kontrolu zahodí na první podmínce.
        $this->commandBus->dispatch(
            new CheckSagaTimeout(
                orderId: $orderId,
                expectedStatus: $status->value,
            ),
            [new DelayStamp($seconds * 1000)],
        );
    }
}
