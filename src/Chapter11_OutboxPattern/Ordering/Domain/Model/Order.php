<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Model;

use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderCancelled;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderPaid;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderPlaced;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderShipped;
use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\EmptyOrderException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\OrderLockedBySagaException;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\ProductId;
use App\Chapter11_OutboxPattern\Shipping\Domain\ValueObject\ShipmentId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * Kanonický agregát z kapitoly o návrhu agregátu, rozšířený o továrnu
 * placeWithItems() z kapitoly Outbox Pattern (15.04).
 *
 * Ukázka ho drží jako vlastní kopii, protože placeWithItems() je statická
 * továrna na téže třídě – zvenku ji ke kanonickému Order přidat nejde.
 * Kapitola o ságách (ukázka Chapter07_Sagas) používá právě tuhle třídu,
 * stejně jako kniha navazuje na továrnu z Outboxu.
 */
final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    // Asymetrická viditelnost: přečte kdokoli, zapíše jen kód uvnitř třídy.
    public private(set) OrderStatus $status;

    // Čas potvrzení drží agregát, protože na něm stojí storno lhůta.
    public private(set) ?\DateTimeImmutable $placedAt = null;

    // Semantic lock: dokud nad objednávkou běží proces, uživatel do ní nesáhne.
    private bool $sagaInProgress = false;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
        $this->status = OrderStatus::Draft;
    }

    // Kanonická továrna knihy: identita a vlastník, nic víc.
    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    /**
     * Druhá továrna vedle kanonického Order::place(OrderId, CustomerId);
     * seznam položek potřebuje integrační událost.
     *
     * Přebírá primitivní řádky z commandu, ne hotové OrderItem: položky
     * vznikají jen uvnitř agregátu.
     *
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public static function placeWithItems(CustomerId $customerId, array $items): self
    {
        // place() nahraje OrderPlaced jako první událost, stejně jako
        // placeWithFirstItem() v kapitole o návrhu agregátu.
        $order = self::place(OrderId::generate(), $customerId);

        foreach ($items as $item) {
            $order->addItem(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                new Money($item['unitPriceInCents'], Currency::CZK),
            );
        }

        // Objednávka přišla kompletní – opouští Draft hned, jinak by na ni
        // sága nemohla zavolat markPaid() a uvázla by v prvním kroku.
        $order->confirm();

        // Od tohohle okamžiku nad objednávkou běží proces. Zámek uvolní
        // až sága, ať skončí úspěchem nebo kompenzací.
        $order->lockForSaga();

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

    public function markPaid(): void
    {
        // Příkaz jde přes asynchronní transport s garancí at-least-once,
        // takže opakované doručení není chyba volajícího.
        if ($this->status === OrderStatus::Paid) {
            return;
        }

        if ($this->status !== OrderStatus::Confirmed) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Paid->value,
            );
        }

        $this->status = OrderStatus::Paid;
        $this->record(new OrderPaid($this->id, new \DateTimeImmutable()));
    }

    public function ship(ShipmentId $shipmentId): void
    {
        if ($this->status === OrderStatus::Shipped) {
            return;
        }

        if ($this->status !== OrderStatus::Paid) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Shipped->value,
            );
        }

        $this->status = OrderStatus::Shipped;
        $this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));
    }

    public function lockForSaga(): void
    {
        $this->sagaInProgress = true;
    }

    public function releaseSagaLock(): void
    {
        $this->sagaInProgress = false;
    }

    public function isLockedBySaga(): bool
    {
        return $this->sagaInProgress;
    }

    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        // Zámek drží proces, ne uživatel.
        if ($this->sagaInProgress) {
            throw new OrderLockedBySagaException($this->id);
        }

        // Odeslanou ani doručenou zásilku storno nevrátí – tam nastupuje kompenzace.
        if (in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Cancelled->value,
            );
        }

        // Opakované storno není chyba volajícího, jen už není co dělat.
        if ($this->status === OrderStatus::Cancelled) {
            return;
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled($this->id, $this->customerId, $reason, $when));
    }

    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }

    public function totalAmount(): Money
    {
        // Guard je nutný: place() prázdnou objednávku pustí, takže bez něj
        // by součet vracel tichou nulu v natvrdo zvolené měně.
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
