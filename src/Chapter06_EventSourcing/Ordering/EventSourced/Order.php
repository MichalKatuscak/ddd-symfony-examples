<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced;

use App\Chapter06_EventSourcing\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\EmptyOrderException;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\InvalidOrderStateTransitionException;
use App\Chapter06_EventSourcing\SharedKernel\Domain\EventSourcedAggregate;

/**
 * Event-sourced model téhož konceptu jako stavově ukládaný Order – proto
 * vlastní namespace. Stav se rekonstruuje replayem, vnitřní vlastnosti
 * zůstávají privátní a čtou se přes gettery.
 */
final class Order extends EventSourcedAggregate
{
    private string $orderId;
    private string $customerId;
    private OrderStatus $status;

    /** @var list<OrderItem> */
    private array $items = [];

    private ?string $trackingNumber = null;

    // Statická továrna – vytvoří objednávku ve stavu Draft
    public static function place(string $orderId, string $customerId): self
    {
        $order = new self();
        $order->recordEvent(OrderPlaced::create($orderId, $customerId));

        return $order;
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Items can only be added to draft orders.');
        }

        $this->recordEvent(OrderItemAdded::create($this->orderId, $item));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Only draft orders can be confirmed.');
        }
        if ($this->items === []) {
            throw new EmptyOrderException('Cannot confirm an empty order.');
        }

        $this->recordEvent(OrderConfirmed::create($this->orderId));
    }

    public function ship(string $trackingNumber): void
    {
        if ($this->status !== OrderStatus::Confirmed) {
            throw new InvalidOrderStateTransitionException('Only confirmed orders can be shipped.');
        }

        $this->recordEvent(OrderShipped::create($this->orderId, $trackingNumber));
    }

    // --- apply* metody – MUSÍ být protected (ne private), aby je base class mohla volat dynamicky ---
    // --- Obsahují POUZE změnu interního stavu, žádnou doménovou logiku ---

    protected function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->orderId    = $event->orderId;
        $this->customerId = $event->customerId;
        $this->items      = [];
        $this->status     = OrderStatus::Draft;
    }

    protected function applyOrderItemAdded(OrderItemAdded $event): void
    {
        $this->items[] = $event->item;
    }

    protected function applyOrderConfirmed(OrderConfirmed $event): void
    {
        $this->status = OrderStatus::Confirmed;
    }

    protected function applyOrderShipped(OrderShipped $event): void
    {
        $this->status         = OrderStatus::Shipped;
        $this->trackingNumber = $event->trackingNumber;
    }

    // Gettery pro aplikační vrstvu
    public function orderId(): string         { return $this->orderId; }
    public function customerId(): string      { return $this->customerId; }
    public function status(): OrderStatus     { return $this->status; }
    public function trackingNumber(): ?string { return $this->trackingNumber; }

    /** @return list<OrderItem> */
    public function items(): array            { return $this->items; }
}
