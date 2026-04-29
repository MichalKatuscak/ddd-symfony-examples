<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Application\PlaceOrder;

use App\Chapter11_OutboxPattern\Domain\Order\Order;
use App\Chapter11_OutboxPattern\Domain\Order\OrderId;
use App\Chapter11_OutboxPattern\Domain\Order\OrderRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxMessage;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Atomický zápis: agregát + outbox řádky vznikají v jedné konceptuální transakci.
 *
 * V produkci s Doctrine ORM by to vypadalo takto:
 *
 *   $em->wrapInTransaction(function () use ($order, $outbox): void {
 *       $em->persist($order);
 *       foreach ($order->pullEvents() as $event) {
 *           $outbox->save(OutboxMessage::fromEvent($event));
 *       }
 *   });
 *
 * Buď se commitnou oba zápisy (Order i Outbox), nebo ani jeden — dual-write
 * problem je vyřešen, protože brokera nezavoláme přímo. O publikování se
 * postará samostatný relay (DispatchOutboxCommand).
 */
#[AsMessageHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private OutboxRepositoryInterface $outbox,
    ) {
    }

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();

        // BEGIN TRANSACTION (konceptuálně)
        try {
            $order = Order::place($id, $command->customerId, $command->amount);
            $this->orders->save($order);

            foreach ($order->pullEvents() as $event) {
                // Místo přímého $bus->dispatch($event) zapíšeme zprávu do
                // outbox tabulky. Stejné DB spojení = stejná transakce.
                $this->outbox->save(OutboxMessage::fromEvent($event));
            }
            // COMMIT
        } catch (\Throwable $e) {
            // ROLLBACK — Order ani outbox řádek se neobjeví.
            throw $e;
        }

        return $id->value;
    }
}
