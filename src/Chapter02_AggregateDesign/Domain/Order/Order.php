<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order;

use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderItemAdded;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPaid;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderShipped;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
// ShipmentId patří cizímu kontextu – přes hranici jde jen identita.
use App\Chapter02_AggregateDesign\Domain\Shipping\ShipmentId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

/**
 * Kanonický agregát z kapitoly Návrh agregátu (07.07).
 *
 * Oproti knize bez perzistence: položky drží pole místo Doctrine
 * Collection a OrderItem nemá zpětnou referenci na kořen. Důvod
 * a mapování popisuje README ukázky.
 */
class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    // Asymetrická viditelnost: přečte kdokoli, zapíše jen kód uvnitř třídy.
    // Getter tím odpadá a stavové přechody zůstávají jediným místem zápisu.
    public private(set) OrderStatus $status;

    // Čas potvrzení drží agregát, protože na něm stojí doménové pravidlo:
    // storno lhůta v kapitole o autorizaci.
    public private(set) ?\DateTimeImmutable $placedAt = null;

    // Semantic lock: dokud nad objednávkou běží proces, nesmí do ní sáhnout
    // uživatel. Podrobněji v kapitole o ságách, sekce Izolace ság.
    private bool $sagaInProgress = false;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId, // POZOR: ID, ne objekt Customer
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

    // Invariant „objednávka má alespoň jednu položku“ vymáhá signatura:
    // bez první položky objednávka nevznikne. Vedle place() je to druhá
    // továrna, ne jeho náhrada.
    public static function placeWithFirstItem(
        CustomerId $customerId,
        ProductId $productId,
        int $quantity,
        Money $unitPrice,
        ?\DateTimeImmutable $at = null,
    ): self {
        $order = self::place(OrderId::generate(), $customerId);
        $order->addItem($productId, $quantity, $unitPrice);
        // Objednávka přišla kompletní, takže rovnou opouští Draft.
        $order->confirm($at);

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

        // INVARIANT: jedna položka na produkt – sčítáme quantity, neduplikujeme
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

    // Čas jde vložit zvenku ze stejného důvodu jako u cancel(): scénář
    // „potvrzeno v 10:00, stornováno ve 12:00“ by se bez toho dal
    // otestovat jen reflexí.
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

    // Bez tohohle přechodu je ship() nedosažitelná: do stavu Paid
    // se objednávka jinak nedostane.
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

    // Čas přebírá parametr, ne new \DateTimeImmutable() uvnitř: kapitola
    // o autorizaci na něm staví storno lhůtu a testy potřebují zadat vlastní.
    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        // Zámek drží proces, ne uživatel. Bez téhle podmínky by storno
        // prošlo uprostřed ságy, ta by dál strhla platbu a vytvořila
        // zásilku k objednávce, která už neexistuje.
        if ($this->sagaInProgress) {
            throw new OrderLockedBySagaException($this->id);
        }

        // Storno je hrana grafu jako každá jiná: odeslanou ani doručenou
        // zásilku zpátky nevrátí, tam nastupuje kompenzace v ságe.
        // Zaplacenou objednávku storno vrátit smí.
        if (in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Cancelled->value,
            );
        }

        // Opakované storno není chyba volajícího, jen už není co dělat.
        // Bez téhle větve by retry ságy shodil handler.
        if ($this->status === OrderStatus::Cancelled) {
            return;
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled(
            $this->id,
            $this->customerId,
            $reason,
            $when,
        ));
    }

    // Vlastnictví patří agregátu, ne Voteru. Kapitola o autorizaci
    // na tom staví celé rozhodování o přístupu.
    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }

    /**
     * Dotaz pro UI z ukázky kapitoly o autorizaci (Chapter10_Authorization).
     * Kapitola Návrh agregátu ho nevypisuje; verze se storno lhůtou
     * a parametrem $now je až v kapitole Autorizace v DDD. Musí znát
     * i zámek – jinak šablona nabídne tlačítko, jehož příkaz vždycky selže.
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
