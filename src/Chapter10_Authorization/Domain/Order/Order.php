<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain\Order;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderItemAdded;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPaid;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderShipped;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderItem;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter02_AggregateDesign\Domain\Shipping\ShipmentId;
use App\Chapter10_Authorization\Domain\Order\Exception\CancellationWindowExpiredException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

/**
 * Kanonický Order z kapitoly Návrh agregátu (ukázka Chapter02_AggregateDesign)
 * s tím, co přidává kapitola Autorizace v DDD (11.06): storno lhůtu 24 h.
 *
 * cancel() a isCancellable() nahrazují kanonické verze celé, ne po
 * částech. Konstruktor, továrny, přechody i hodnotové objekty a události
 * jsou tytéž – události a výjimky se importují z Chapter02, nekopírují.
 * Třídu ukázka nese sama, protože kanonický agregát má stav zapisovatelný
 * jen zevnitř (private(set)) a podtřída by ho přepnout nemohla.
 */
final class Order extends AggregateRoot
{
    private const CANCELLATION_WINDOW_SECONDS = 86_400; // 24 h

    /** @var list<OrderItem> */
    private array $items = [];

    public private(set) OrderStatus $status;

    // Čas potvrzení drží agregát, protože na něm stojí storno lhůta.
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

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    public static function placeWithFirstItem(
        CustomerId $customerId,
        ProductId $productId,
        int $quantity,
        Money $unitPrice,
        ?\DateTimeImmutable $at = null,
    ): self {
        $order = self::place(OrderId::generate(), $customerId);
        $order->addItem($productId, $quantity, $unitPrice);
        $order->confirm($at);

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

    // Čas jde vložit zvenku: scénář „potvrzeno v 10:00, stornováno
    // ve 12:00“ by se bez toho dal otestovat jen reflexí.
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

    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        // Zámek drží proces – viz kapitola o ságách, sekce Izolace ság.
        if ($this->sagaInProgress) {
            throw new OrderLockedBySagaException($this->id);
        }

        // Odeslanou ani doručenou zásilku storno nevrátí – tam nastupuje
        // kompenzace v ságe. Zaplacenou objednávku storno vrátit smí.
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

        // Lhůta běží od potvrzení. Draft ji ještě nemá a rozpracovaný
        // košík také nikdo neruší na čas.
        if ($this->placedAt !== null) {
            $age = $when->getTimestamp() - $this->placedAt->getTimestamp();

            if ($age > self::CANCELLATION_WINDOW_SECONDS) {
                throw new CancellationWindowExpiredException(
                    $this->id,
                    $this->placedAt,
                    $when,
                );
            }
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled(
            orderId:    $this->id,
            customerId: $this->customerId,
            reason:     $reason,
            occurredAt: $when,
        ));
    }

    public function isCancellable(\DateTimeImmutable $now): bool
    {
        // Šablona se ptá právě této metody, takže musí znát i zámek.
        // Jinak nabídne tlačítko, jehož příkaz skončí v dead-letter frontě.
        if ($this->sagaInProgress) {
            return false;
        }

        if (in_array($this->status, [
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ], true)) {
            return false;
        }

        return $this->placedAt === null
            || $now->getTimestamp() - $this->placedAt->getTimestamp()
               <= self::CANCELLATION_WINDOW_SECONDS;
    }

    // Vztahový invariant: vlastnictví zná agregát, ne Voter
    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }

    public function totalAmount(): Money
    {
        if ($this->items === []) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $total = $this->items[0]->subtotal(); // měnu určuje první položka

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }
}
