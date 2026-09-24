<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderPlaced;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\EmptyOrderException;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

/**
 * Agregát ze sekce 06.05, doplněný o záznam událostí ze sekce 06.09.
 * Plnou verzi (placeWithFirstItem, markPaid, ship, zámek ságy) ukazuje
 * Chapter02_AggregateDesign.
 */
class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    private function __construct(
        // Identita i vlastník jsou veřejné readonly vlastnosti, stejně jako
        // v kanonickém agregátu z kapitoly Návrh agregátu.
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
        // Konstruktor událost nezaznamenává: ruční reconstitution ho volá
        // a každé načtení objednávky by jinak znovu ohlásilo její vznik.
        $this->status = OrderStatus::Draft;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::notAllowedInState(
                'přidání položky',
                $this->status->value,
            );
        }

        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
        $this->record(new OrderItemAdded($this->id, $productId, $quantity));
    }

    public function removeItem(ProductId $productId): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::notAllowedInState(
                'odebrání položky',
                $this->status->value,
            );
        }

        $this->items = array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => !$item->productId->equals($productId),
        ));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Confirmed->value,
            );
        }

        if ($this->items === []) {
            throw EmptyOrderException::cannotConfirm();
        }

        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed($this->id, $this->customerId, new \DateTimeImmutable()));
    }

    // Důvod a čas storna nese událost OrderCancelled (viz Návrh agregátu);
    // tato podoba bez událostí je jen přijímá.
    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        // Odeslanou ani doručenou zásilku storno nevrátí, zaplacenou objednávku ano.
        if (in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Cancelled->value,
            );
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function totalAmount(): Money
    {
        if ($this->items === []) {
            throw EmptyOrderException::cannotBePlaced();
        }

        // Měnu určuje první položka; Money::add() při nesouladu měn vyhodí
        // výjimku, takže objednávka ve dvou měnách neprojde tiše.
        $total = $this->items[0]->unitPrice->multiply($this->items[0]->quantity);

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->unitPrice->multiply($item->quantity));
        }

        return $total;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    // Základní podoba s getterem. Od PHP 8.4 ho nahradí asymetrická
    // viditelnost public private(set), viz kapitola Návrh agregátu.
    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
