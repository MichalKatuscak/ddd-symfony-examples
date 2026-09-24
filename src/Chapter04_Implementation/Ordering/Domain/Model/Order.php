<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Ordering\Domain\Model;

use App\Chapter04_Implementation\Ordering\Domain\Event\OrderPlaced;
use App\Chapter04_Implementation\Ordering\Domain\Event\OrderStatusChanged;
use App\Chapter04_Implementation\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\AggregateRoot;

/**
 * Výřez z kapitoly 10.08: enum OrderStatus použitý v agregátu.
 *
 * transitionTo() je ALTERNATIVA k pojmenovaným přechodům, ne jejich
 * doplněk. Kanonický Order z kapitoly Návrh agregátu má markPaid(),
 * ship() a cancel(), protože každá operace nese vlastní invariant
 * a vlastní událost (ukázka Chapter02_AggregateDesign).
 */
final class Order extends AggregateRoot
{
    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
        $this->status = OrderStatus::Draft;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    // Getter drží výřez krátký; kanonický Order čte stav přes
    // public private(set), viz kapitola Návrh agregátu.
    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function transitionTo(OrderStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                $newStatus->value,
            );
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        $this->record(new OrderStatusChanged(
            $this->id,
            $oldStatus,
            $newStatus,
            new \DateTimeImmutable(),
        ));
    }
}
