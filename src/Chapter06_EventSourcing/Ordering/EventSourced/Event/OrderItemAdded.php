<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced\Event;

use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class OrderItemAdded extends DomainEvent
{
    private function __construct(
        string $eventId,
        DateTimeImmutable $occurredAt,
        public readonly string $orderId,
        public readonly OrderItem $item,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public static function create(string $orderId, OrderItem $item): self
    {
        return new self(
            eventId:    (string) Uuid::v7(),
            occurredAt: new DateTimeImmutable('now', new \DateTimeZone('UTC')),
            orderId:    $orderId,
            item:       $item,
        );
    }

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            eventId:    $payload['eventId'],
            occurredAt: new DateTimeImmutable($payload['occurredAt'], new \DateTimeZone('UTC')),
            orderId:    $payload['orderId'],
            // Hodnotový objekt uvnitř události se skládá zpět ručně;
            // serializer zná jen skalární payload.
            item:       new OrderItem(
                $payload['item']['productId'],
                $payload['item']['quantity'],
                $payload['item']['unitPriceInCents'],
            ),
        );
    }

    public function eventType(): string { return 'ordering.order_item_added'; }

    public function schemaVersion(): int { return 1; }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'eventId'    => $this->eventId,
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s.u'),
            'orderId'    => $this->orderId,
            'item'       => [
                'productId'        => $this->item->productId,
                'quantity'         => $this->item->quantity,
                'unitPriceInCents' => $this->item->unitPriceInCents,
            ],
        ];
    }
}
