<?php

declare(strict_types=1);
namespace App\Chapter06_EventSourcing\Domain\Order;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Shared\Domain\DomainEvent;

final class Order
{
    private string $status = 'pending';
    private int $totalAmount = 0;
    private string $customerId = '';
    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];

    private function __construct(private readonly OrderId $id) {}

    public static function place(OrderId $id, string $customerId, int $totalAmount): self
    {
        $order = new self($id);
        $event = new OrderPlaced($id->value, $customerId, $totalAmount);
        $order->apply($event);
        $order->uncommittedEvents[] = $event;
        return $order;
    }

    /** @param DomainEvent[] $events */
    public static function reconstruct(OrderId $id, array $events): self
    {
        $order = new self($id);
        foreach ($events as $event) {
            $order->apply($event);
        }
        return $order;
    }

    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Cannot confirm order in status: ' . $this->status);
        }
        $event = new OrderConfirmed($this->id->value);
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    public function cancel(string $reason): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Cannot cancel order in status: ' . $this->status);
        }
        $event = new OrderCancelled($this->id->value, $reason);
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    private function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof OrderPlaced => (function () use ($event) {
                $this->status = 'pending';
                $this->totalAmount = $event->totalAmount;
                $this->customerId = $event->customerId;
            })(),
            $event instanceof OrderConfirmed => (function () { $this->status = 'confirmed'; })(),
            $event instanceof OrderCancelled => (function () { $this->status = 'cancelled'; })(),
            default => throw new \LogicException('Unknown event: ' . get_class($event)),
        };
    }

    public function id(): OrderId { return $this->id; }
    public function status(): string { return $this->status; }
    public function totalAmount(): int { return $this->totalAmount; }
    public function customerId(): string { return $this->customerId; }

    /** @return DomainEvent[] */
    public function pullUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];
        return $events;
    }
}
