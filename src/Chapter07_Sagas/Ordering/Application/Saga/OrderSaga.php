<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Saga;

/**
 * Stav ságy. Kniha ho mapuje jako Doctrine entitu na tabulku order_saga
 * s UNIQUE (saga_type, correlation_id) a sloupcem version pro optimistické
 * zamykání. Ukázka stav drží v paměti (InMemoryOrderSagaRepository), takže
 * mapování i verze odpadají; unikátnost korelace hlídá repozitář.
 */
class OrderSaga
{
    private string $sagaType;

    private string $correlationId;

    private string $status;

    /** @var array<string, mixed> */
    private array $context = [];

    /** @var list<string> */
    private array $processedEventIds = [];

    private \DateTimeImmutable $startedAt;

    private \DateTimeImmutable $updatedAt;

    private ?\DateTimeImmutable $completedAt = null;

    private function __construct() {}

    /** @param array<string, mixed> $context */
    public static function start(
        string $sagaType,
        string $correlationId,
        OrderSagaStatus $status,
        array $context = [],
    ): self {
        $state = new self();
        $state->sagaType = $sagaType;
        $state->correlationId = $correlationId;
        $state->status = $status->value;
        $state->context = $context;
        $state->startedAt = new \DateTimeImmutable();
        $state->updatedAt = new \DateTimeImmutable();

        return $state;
    }

    public function transitionTo(OrderSagaStatus $newStatus): void
    {
        $this->status = $newStatus->value;
        $this->updatedAt = new \DateTimeImmutable();

        if ($newStatus === OrderSagaStatus::Completed || $newStatus === OrderSagaStatus::Failed) {
            $this->completedAt = new \DateTimeImmutable();
        }
    }

    /** @return bool zda se přechod opravdu odehrál */
    public function applyPaymentSucceeded(string $eventId): bool
    {
        return $this->applyStep($eventId, OrderSagaStatus::AwaitingPayment, OrderSagaStatus::AwaitingStockReservation);
    }

    /** @return bool zda se přechod opravdu odehrál */
    public function applyStockReserved(string $eventId): bool
    {
        return $this->applyStep($eventId, OrderSagaStatus::AwaitingStockReservation, OrderSagaStatus::AwaitingShipment);
    }

    /** @return bool zda se přechod opravdu odehrál */
    public function applyShipmentCreated(string $eventId): bool
    {
        return $this->applyStep($eventId, OrderSagaStatus::AwaitingShipment, OrderSagaStatus::Completed);
    }

    public function status(): OrderSagaStatus
    {
        return OrderSagaStatus::from($this->status);
    }

    public function sagaType(): string
    {
        return $this->sagaType;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }

    public function updateContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function hasProcessed(string $eventId): bool
    {
        return in_array($eventId, $this->processedEventIds, true);
    }

    public function markProcessed(string $eventId): void
    {
        $this->processedEventIds[] = $eventId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function isTerminated(): bool
    {
        return $this->completedAt !== null;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Idempotentní přechod: stejná událost podruhé ani událost ve stavu,
     * kde ji sága nečeká, stav nezmění.
     */
    private function applyStep(string $eventId, OrderSagaStatus $expected, OrderSagaStatus $next): bool
    {
        // 1) Idempotence: stejný event už zpracován? Skip.
        if ($this->hasProcessed($eventId)) {
            return false;
        }

        // 2) Guard stavového automatu: smí přechod nastat?
        if ($this->status() !== $expected) {
            // Out-of-order: událost dorazila ve stavu, kde ji nečekáme.
            return false;
        }

        $this->transitionTo($next);
        $this->markProcessed($eventId);

        return true;
    }
}
