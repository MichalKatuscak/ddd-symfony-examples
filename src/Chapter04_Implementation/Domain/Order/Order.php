<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    private string $id;

    private string $customerId;

    private int $totalAmount = 0;

    private string $status;

    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id->value;
        $this->customerId = $customerId;
        $this->status = 'pending';
    }

    /**
     * @param array<array{name: string, qty: int, price: int}> $items
     */
    public static function place(OrderId $id, string $customerId, array $items): self
    {
        $order = new self($id, $customerId);
        foreach ($items as $item) {
            $order->items[] = $item;
            $order->totalAmount += $item['price'] * $item['qty'];
        }
        $order->record(new OrderPlaced($id->value, $customerId, $order->totalAmount));
        return $order;
    }

    public function id(): OrderId { return new OrderId($this->id); }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return OrderStatus::from($this->status); }
    public function total(): Money { return new Money($this->totalAmount, 'CZK'); }
    /** @return array<array{name: string, qty: int, price: int}> */
    public function items(): array { return $this->items; }
}
