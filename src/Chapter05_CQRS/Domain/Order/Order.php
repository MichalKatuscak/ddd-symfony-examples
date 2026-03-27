<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Domain\Order;
use App\Shared\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    private string $id;

    private string $customerId;

    private int $totalAmount = 0;

    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id->value;
        $this->customerId = $customerId;
    }

    /** @param array<array{name: string, qty: int, price: int}> $items */
    public static function place(OrderId $id, string $customerId, array $items): self
    {
        $order = new self($id, $customerId);
        foreach ($items as $item) {
            $order->items[] = $item;
            $order->totalAmount += $item['price'] * $item['qty'];
        }
        $order->record(new OrderPlaced($id->value, $customerId));
        return $order;
    }

    public function id(): OrderId { return new OrderId($this->id); }
    public function customerId(): string { return $this->customerId; }
    public function total(): Money { return new Money($this->totalAmount, 'CZK'); }
    public function items(): array { return $this->items; }
}
