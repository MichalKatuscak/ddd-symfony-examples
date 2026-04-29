<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

use App\Shared\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Order extends AggregateRoot
{
    private OrderStatus $status;

    private function __construct(
        private readonly OrderId $id,
        private readonly string $customerId,
        private readonly int $amount,
    ) {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Order amount must be positive');
        }
        if ($customerId === '') {
            throw new \InvalidArgumentException('CustomerId cannot be empty');
        }
    }

    public static function place(OrderId $id, string $customerId, int $amount): self
    {
        $order = new self($id, $customerId, $amount);
        $order->status = OrderStatus::Placed;

        $order->record(new OrderPlaced(
            eventId: Uuid::v4()->toRfc4122(),
            orderId: $id->value,
            customerId: $customerId,
            amount: $amount,
        ));

        return $order;
    }

    public function cancel(string $reason): void
    {
        if ($this->status === OrderStatus::Cancelled) {
            throw new \DomainException('Order is already cancelled');
        }

        $this->status = OrderStatus::Cancelled;

        $this->record(new OrderCancelled(
            eventId: Uuid::v4()->toRfc4122(),
            orderId: $this->id->value,
            reason: $reason,
        ));
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }
}
