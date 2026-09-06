<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\Currency;
use App\Shared\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    // Identita je hodnotový objekt i v perzistenci – převod obstará
    // custom Doctrine typ ch04_order_id, ne getter.
    public readonly OrderId $id;

    private string $customerId;

    private int $totalAmount = 0;

    private string $status;

    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->status = 'pending';
    }

    /** @param OrderLine[] $lines */
    public static function place(OrderId $id, string $customerId, array $lines): self
    {
        $order = new self($id, $customerId);
        foreach ($lines as $line) {
            $order->items[] = $line->toArray();
            $order->totalAmount += $line->lineTotal()->amountInCents;
        }
        $order->record(new OrderPlaced($id->value, $customerId, $order->totalAmount));
        return $order;
    }

    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return OrderStatus::from($this->status); }
    public function totalAmount(): Money { return new Money($this->totalAmount, Currency::CZK); }
    /** @return array<array{name: string, qty: int, price: int}> */
    public function items(): array { return $this->items; }
}
