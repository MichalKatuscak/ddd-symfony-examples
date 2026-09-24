<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Handler;

use App\Chapter07_Sagas\Ordering\Application\Command\ReleaseOrderLock;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ReleaseOrderLockHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function __invoke(ReleaseOrderLock $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));
        $order->releaseSagaLock();

        // Žádná událost. Uvolnění zámku není doménová změna, na kterou
        // by někdo čekal – jen konec výhradního přístupu procesu.
        $this->orders->save($order);
    }
}
