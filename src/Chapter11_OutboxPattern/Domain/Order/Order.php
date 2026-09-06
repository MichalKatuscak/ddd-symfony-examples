<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

use App\Chapter11_OutboxPattern\Domain\Order\Exception\OrderAlreadyCancelledException;
use App\Shared\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Order extends AggregateRoot
{
    // Asymetrická viditelnost: přečte kdokoli, zapíše jen kód uvnitř třídy.
    public private(set) OrderStatus $status;

    private function __construct(
        public readonly OrderId $id,
        public readonly string $customerId,
        public readonly int $amount,
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
            eventId: Uuid::v7()->toRfc4122(),
            orderId: $id->value,
            customerId: $customerId,
            amount: $amount,
        ));

        return $order;
    }

    public function cancel(string $reason): void
    {
        if ($this->status === OrderStatus::Cancelled) {
            throw OrderAlreadyCancelledException::withId($this->id->value);
        }

        $this->status = OrderStatus::Cancelled;

        $this->record(new OrderCancelled(
            eventId: Uuid::v7()->toRfc4122(),
            orderId: $this->id->value,
            reason: $reason,
        ));
    }

}
