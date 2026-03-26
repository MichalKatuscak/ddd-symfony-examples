<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Shared\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var OrderItem[] */
    private array $items = [];
    private OrderStatus $status;

    private function __construct(
        private readonly OrderId $id,
        private readonly string $customerId,
    ) {
        $this->status = OrderStatus::pending();
    }

    public static function create(OrderId $id, string $customerId): self
    {
        return new self($id, $customerId);
    }

    public function addItem(string $name, int $qty, Money $unitPrice): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Cannot add items to a non-pending order');
        }
        $item = new OrderItem($name, $qty, $unitPrice);
        $this->items[] = $item;
        $this->record(new OrderItemAdded(
            orderId: $this->id->value,
            productName: $name,
            qty: $qty,
            lineTotalCents: $item->lineTotal()->amount,
        ));
    }

    public function confirm(): void
    {
        if (empty($this->items)) {
            throw new \DomainException('Cannot confirm an empty order');
        }
        $this->status = OrderStatus::confirmed();
        $this->record(new OrderConfirmed(
            orderId: $this->id->value,
            totalAmount: $this->total()->amount,
        ));
    }

    public function id(): OrderId { return $this->id; }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }

    public function total(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->lineTotal()),
            new Money(0, 'CZK'),
        );
    }

    /** @return OrderItem[] */
    public function items(): array { return $this->items; }
}
