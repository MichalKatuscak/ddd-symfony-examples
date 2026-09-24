<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Handler;

use App\Chapter07_Sagas\Ordering\Application\Command\MarkOrderPaid;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class MarkOrderPaidHandler
{
    public function __construct(
        private OrderRepository $orders,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(MarkOrderPaid $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));
        // markPaid() je idempotentní: opakované doručení příkazu tiše skončí.
        $order->markPaid();
        $this->orders->save($order);

        // markPaid() nahrává OrderPaid. Dispatch je tu v pořádku: event.bus
        // je synchronní a nic neopouští proces. Kdyby událost mířila do
        // brokera, patřila by do outboxu.
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
