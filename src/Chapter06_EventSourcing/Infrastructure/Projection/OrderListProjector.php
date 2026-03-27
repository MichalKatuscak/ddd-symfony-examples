<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Projection;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Shared\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final class OrderListProjector
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @param DomainEvent[] $events */
    public function project(array $events): void
    {
        foreach ($events as $event) {
            match (true) {
                $event instanceof OrderPlaced => $this->onOrderPlaced($event),
                $event instanceof OrderConfirmed => $this->onOrderConfirmed($event),
                $event instanceof OrderCancelled => $this->onOrderCancelled($event),
                default => null,
            };
        }
        $this->em->flush();
    }

    private function onOrderPlaced(OrderPlaced $event): void
    {
        $projection = new OrderListProjection(
            $event->orderId,
            $event->customerId,
            $event->totalAmount,
            'pending',
            $event->occurredAt(),
        );
        $this->em->persist($projection);
    }

    private function onOrderConfirmed(OrderConfirmed $event): void
    {
        $projection = $this->em->find(OrderListProjection::class, $event->orderId);
        $projection?->updateStatus('confirmed');
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $projection = $this->em->find(OrderListProjection::class, $event->orderId);
        $projection?->updateStatus('cancelled');
    }
}
