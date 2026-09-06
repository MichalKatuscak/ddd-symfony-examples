<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\EmptyOrderException;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\InvalidOrderStateTransitionException;
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
        $this->status = OrderStatus::Draft;
    }

    public static function place(OrderId $id, string $customerId): self
    {
        return new self($id, $customerId);
    }

    public function addItem(string $name, int $qty, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::notAllowedInState('přidání položky', $this->status->value);
        }
        $item = new OrderItem($name, $qty, $unitPrice);
        $this->items[] = $item;
        $this->record(new OrderItemAdded(
            orderId: $this->id->value,
            productName: $name,
            qty: $qty,
            lineTotalCents: $item->lineTotal()->amountInCents,
        ));
    }

    public function confirm(): void
    {
        if (empty($this->items)) {
            throw EmptyOrderException::cannotConfirm();
        }
        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed(
            orderId: $this->id->value,
            totalAmount: $this->totalAmount()->amountInCents,
        ));
    }

    public function id(): OrderId { return $this->id; }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }

    public function totalAmount(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->lineTotal()),
            new Money(0, Currency::CZK),
        );
    }

    /** @return OrderItem[] */
    public function items(): array { return $this->items; }
}
