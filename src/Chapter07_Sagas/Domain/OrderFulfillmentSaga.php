<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

use App\Chapter07_Sagas\Domain\Events\CompensationStarted;
use App\Chapter07_Sagas\Domain\Events\OrderShipped;
use App\Chapter07_Sagas\Domain\Events\PaymentFailed;
use App\Chapter07_Sagas\Domain\Events\PaymentProcessed;
use App\Chapter07_Sagas\Domain\Events\SagaCompleted;
use App\Chapter07_Sagas\Domain\Events\SagaFailed;
use App\Chapter07_Sagas\Domain\Events\SagaStarted;
use App\Chapter07_Sagas\Domain\Events\ShipmentFailed;
use App\Chapter07_Sagas\Domain\Events\StockReservationFailed;
use App\Chapter07_Sagas\Domain\Events\StockReserved;
use App\Shared\Domain\DomainEvent;

final class OrderFulfillmentSaga
{
    private SagaState $state;
    private int $amount;
    /** @var SagaStep[] */
    private array $steps = [];
    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];
    private \DateTimeImmutable $createdAt;

    private function __construct(
        private readonly string $id,
        private readonly string $orderId,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function start(string $id, string $orderId, int $amount): self
    {
        $saga = new self($id, $orderId);
        $saga->state = SagaState::Started;
        $saga->amount = $amount;
        $saga->addStep('StartSaga', 'ok');
        $saga->recordEvent(new SagaStarted($id, $orderId));

        return $saga;
    }

    public function handleStockReserved(): void
    {
        $this->state = SagaState::StockReserved;
        $this->addStep('ReserveStock', 'ok');
        $this->recordEvent(new StockReserved($this->id, $this->orderId));
    }

    public function handleStockFailed(string $reason): void
    {
        $this->state = SagaState::Failed;
        $this->addStep('ReserveStock', 'failed');
        $this->recordEvent(new StockReservationFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new SagaFailed($this->id, $reason));
    }

    public function handlePaymentProcessed(): void
    {
        $this->state = SagaState::PaymentProcessed;
        $this->addStep('ProcessPayment', 'ok');
        $this->recordEvent(new PaymentProcessed($this->id, $this->orderId, $this->amount));
    }

    public function handlePaymentFailed(string $reason): void
    {
        $this->state = SagaState::Compensating;
        $this->addStep('ProcessPayment', 'failed');
        $this->recordEvent(new PaymentFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new CompensationStarted($this->id, 'ProcessPayment'));
    }

    public function handleShipmentCompleted(): void
    {
        $this->state = SagaState::Completed;
        $this->addStep('ShipOrder', 'ok');
        $this->recordEvent(new OrderShipped($this->id, $this->orderId));
        $this->recordEvent(new SagaCompleted($this->id));
    }

    public function handleShipmentFailed(string $reason): void
    {
        $this->state = SagaState::Compensating;
        $this->addStep('ShipOrder', 'failed');
        $this->recordEvent(new ShipmentFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new CompensationStarted($this->id, 'ShipOrder'));
    }

    public function compensateStock(): void
    {
        $this->addStep('CompensateStock', 'compensated');
    }

    public function compensatePayment(): void
    {
        $this->addStep('CompensatePayment', 'compensated');
    }

    public function markFailed(string $reason): void
    {
        $this->state = SagaState::Failed;
        $this->recordEvent(new SagaFailed($this->id, $reason));
    }

    /** @return DomainEvent[] */
    public function pullUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];

        return $events;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function state(): SagaState
    {
        return $this->state;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    /** @return SagaStep[] */
    public function steps(): array
    {
        return $this->steps;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function addStep(string $name, string $status): void
    {
        $this->steps[] = new SagaStep($name, $status);
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }
}
