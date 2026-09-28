<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Model;

use App\Chapter05_CQRS\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderPlaced;
use App\Chapter05_CQRS\Ordering\Domain\Exception\EmptyOrderException;
use App\Chapter05_CQRS\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * Výřez kanonického Order (kapitola Návrh agregátu) s továrnou
 * placeWithItems() z kapitoly Outbox Pattern. Obsahuje jen to, co
 * potřebuje příkaz PlaceOrder – o write model v kapitole CQRS nejde.
 *
 * Kanonická placeWithItems() na konci volá lockForSaga(). Ukázka ságu
 * nemá, takže zámek i jeho uvolnění vynechává.
 */
final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    public private(set) OrderStatus $status;

    // Čas potvrzení; na něm v kanonickém modelu stojí storno lhůta.
    public private(set) ?\DateTimeImmutable $placedAt = null;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
        $this->status = OrderStatus::Draft;
    }

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    /**
     * Druhá továrna vedle kanonického Order::place(OrderId, CustomerId).
     * Přebírá primitivní řádky z commandu, položky vznikají jen uvnitř agregátu.
     *
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public static function placeWithItems(CustomerId $customerId, array $items): self
    {
        // place() nahraje OrderPlaced jako první událost.
        $order = self::place(OrderId::generate(), $customerId);

        foreach ($items as $item) {
            $order->addItem(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                new Money($item['unitPriceInCents'], Currency::CZK),
            );
        }

        // Objednávka přišla kompletní – opouští Draft hned.
        $order->confirm();

        return $order;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::notAllowedInState(
                'addItem',
                $this->status->value,
            );
        }

        // Invariant: jedna položka na produkt – množství se sčítá, neduplikuje.
        foreach ($this->items as $existing) {
            if ($existing->productId->equals($productId)) {
                $existing->increaseQuantity($quantity);
                $this->record(new OrderItemAdded($this->id, $productId, $quantity));

                return;
            }
        }

        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
        $this->record(new OrderItemAdded($this->id, $productId, $quantity));
    }

    public function confirm(?\DateTimeImmutable $at = null): void
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
        $this->placedAt = $at ?? new \DateTimeImmutable();
        $this->record(new OrderConfirmed($this->id, $this->customerId, $this->placedAt));
    }

    public function totalAmount(): Money
    {
        // Guard je nutný: kanonické place() prázdnou objednávku pustí.
        // Bez něj by součet vracel tichou nulu v natvrdo zvolené měně.
        if ($this->items === []) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $total = $this->items[0]->subtotal(); // měnu určuje první položka

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }
}
