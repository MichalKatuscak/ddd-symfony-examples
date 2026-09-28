<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\Handler;

use App\Chapter05_CQRS\Ordering\Application\Command\PlaceOrder;
use App\Chapter05_CQRS\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderPlaced;
use App\Chapter05_CQRS\Ordering\Domain\Model\Order;
use App\Chapter05_CQRS\Ordering\Domain\Model\OrderItem;
use App\Chapter05_CQRS\Ordering\Domain\Repository\OrderRepository;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler je navázaný na jednu sběrnici. Bez parametru `bus` by ho
 * Messenger zaregistroval na všechny a dotaz omylem odeslaný na command
 * bus by prošel (kniha 12.05). Command bus kniha jmenuje command.bus,
 * sdílená konfigurace ukázek messenger.bus.command.
 *
 * Vrací OrderId: kontroler si ho vyzvedne z HandledStamp. Funguje to
 * jen na synchronní sběrnici (12.06, Mají commands vracet hodnotu?).
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(PlaceOrder $command): OrderId
    {
        $order = Order::placeWithItems(
            CustomerId::fromString($command->customerId),
            $command->items,
        );

        $this->orders->save($order);

        // Kniha integrační událost ukládá do outboxu ve stejné transakci
        // jako agregát (kapitola Outbox Pattern, ukázka Chapter11) a z něj ji
        // publikuje relay. Ukázka outbox nemá a pošle ji po uložení na sběrnici
        // událostí v témže procesu. Spadne-li proces mezi uložením a dispatchem,
        // projekce se tiše rozejde s write modelem – proto v produkci Outbox.
        foreach ($order->releaseEvents() as $event) {
            // Posluchači uvnitř kontextu dostanou každou doménovou událost
            // synchronně, stejně jako v kanonickém handleru z kapitoly Outbox.
            $this->eventBus->dispatch($event);

            // Integrační tvar má jen OrderPlaced – nese celou objednávku
            // i s položkami. Dílčí události zůstávají uvnitř kontextu;
            // neznámá událost je chyba v překladu, ne něco k tichému přeskočení.
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
                default => throw new \LogicException(
                    'Missing integration translation for ' . $event::class,
                ),
            };

            if ($integrationEvent === null) {
                continue;
            }

            $this->eventBus->dispatch($integrationEvent);
        }

        return $order->id;
    }
}
