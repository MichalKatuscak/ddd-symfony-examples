<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order;

use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderShipped;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

/**
 * Kanonický agregát z kapitoly o návrhu agregátu.
 *
 * Drží tři věci, na kterých kapitola staví: hranici konzistence (položky
 * patří dovnitř, zákazník a produkt jen identitou), uzavřený stavový graf
 * a zámek pro dlouhotrvající proces.
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
        public readonly CustomerId $customerId, // POZOR: ID, ne objekt Customer
    ) {
        $this->status = OrderStatus::Draft;
    }

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        return new self($id, $customerId);
    }

    /**
     * Invariant „objednávka má alespoň jednu položku“ vymáhá signatura –
     * bez první položky objednávka nevznikne.
     */
    public static function placeWithFirstItem(
        CustomerId $customerId,
        ProductId $productId,
        int $quantity,
        Money $unitPrice,
        ?\DateTimeImmutable $at = null,
    ): self {
        $order = new self(OrderId::generate(), $customerId);
        $order->addItem($productId, $quantity, $unitPrice);
        $order->confirm($at);
        $order->record(new OrderPlaced($order->id, $customerId));

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

                return;
            }
        }

        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
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
    }

    // Bez tohohle přechodu je ship() nedosažitelná: do Paid se objednávka jinak nedostane.
    public function markPaid(): void
    {
        if ($this->status !== OrderStatus::Confirmed) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Paid->value,
            );
        }

        $this->status = OrderStatus::Paid;
    }

    public function ship(ShipmentId $shipmentId): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Shipped->value,
            );
        }

        $this->status = OrderStatus::Shipped;
        $this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));
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

    // Vztahový invariant: vlastnictví zná agregát, ne Voter.
    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }

    /**
     * Odpověď pro UI. Musí znát i zámek – jinak šablona nabídne tlačítko,
     * jehož příkaz vždycky skončí v dead-letter frontě.
     */
    public function isCancellable(): bool
    {
        if ($this->sagaInProgress) {
            return false;
        }

        return !in_array($this->status, [
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ], true);
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
