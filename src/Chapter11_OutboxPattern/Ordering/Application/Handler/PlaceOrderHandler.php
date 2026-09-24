<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Application\Handler;

use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderPlaced;
use App\Chapter11_OutboxPattern\Ordering\Domain\Model\Order;
use App\Chapter11_OutboxPattern\Ordering\Domain\Model\OrderItem;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Outbox\Application\DomainEventSerializer;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRepository;
use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Atomický zápis objednávky a outbox řádku.
 *
 * Kniha obaluje tělo do $em->wrapInTransaction(): buď se zapíše objednávka
 * i všechny outbox řádky, nebo nic. Repozitáře ukázky drží data v paměti
 * a transakci nemají, takže hranici transakce zde vyznačuje jen komentář.
 * Princip se nemění – broker se z handleru nevolá.
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OutboxRepository $outbox,
        private DomainEventSerializer $serializer,
    ) {}

    public function __invoke(PlaceOrder $command): OrderId
    {
        // BEGIN (v knize $em->wrapInTransaction)
        $order = Order::placeWithItems(
            CustomerId::fromString($command->customerId),
            $command->items,
        );

        $this->orders->save($order);

        // Doménová událost se do outboxu nedává přímo: nese hodnotové
        // objekty. Na hranici kontextu se překládá na integrační tvar.
        foreach ($order->releaseEvents() as $event) {
            // placeWithItems() nahraje OrderPlaced, OrderItemAdded za každou
            // položku a OrderConfirmed. Integrační tvar má jen OrderPlaced –
            // nese celou objednávku včetně položek. Neznámá událost je chyba
            // v překladu, ne něco k tichému přeskočení.
            $integrationEvent = match (true) {
                $event instanceof OrderItemAdded,
                $event instanceof OrderConfirmed => null,
                $event instanceof OrderPlaced => new OrderPlacedIntegrationEvent(
                    eventId: Uuid::v7(),
                    orderId: $event->orderId->value,
                    customerId: $event->customerId->value,
                    items: array_map(
                        static fn (OrderItem $i): array => [
                            'productId' => $i->productId->value,
                            'quantity' => $i->quantity,
                            'unitPriceInCents' => $i->unitPrice->amountInCents,
                        ],
                        $order->items(),
                    ),
                    totalAmountCents: $order->totalAmount()->amountInCents,
                    occurredAt: $event->occurredAt,
                ),
                default => throw new \LogicException('Chybí překlad pro ' . $event::class),
            };

            if ($integrationEvent === null) {
                continue;
            }

            $this->outbox->store(
                OutboxMessage::fromIntegrationEvent(
                    $integrationEvent,
                    aggregateType: 'Order',
                    aggregateId: $order->id->value,
                    serializer: $this->serializer->serialize(...),
                ),
            );
        }
        // COMMIT

        return $order->id;
    }
}
