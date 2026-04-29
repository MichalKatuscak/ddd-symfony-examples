<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Shared\Domain\AggregateRoot;

/**
 * Aggregate Root — objednávka.
 *
 * Demonstrace "Factory as method" (named constructor): privátní konstruktor
 * + statická pojmenovaná metoda Order::place(), která zaručuje invariant
 * "objednávka má alespoň 1 položku".
 */
final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items;

    /** @param list<OrderItem> $items */
    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        array $items,
        private readonly string $shippingCountry,
        private readonly \DateTimeImmutable $placedAt,
    ) {
        $this->items = $items;
    }

    /**
     * Static factory method — pojmenovaný konstruktor s validací invariantu.
     *
     * @param list<OrderItem> $items
     */
    public static function place(
        CustomerId $customerId,
        array $items,
        string $shippingCountry,
        ?\DateTimeImmutable $placedAt = null,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_values($items),
            shippingCountry: strtoupper($shippingCountry),
            placedAt: $placedAt ?? new \DateTimeImmutable(),
        );

        $order->record(new OrderPlaced(
            orderId: $order->id,
            customerId: $customerId,
            total: $order->total(),
        ));

        return $order;
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function shippingCountry(): string
    {
        return $this->shippingCountry;
    }

    public function placedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function total(): Money
    {
        $total = new Money(0);
        foreach ($this->items as $item) {
            $total = $total->add($item->lineTotal());
        }

        return $total;
    }
}
