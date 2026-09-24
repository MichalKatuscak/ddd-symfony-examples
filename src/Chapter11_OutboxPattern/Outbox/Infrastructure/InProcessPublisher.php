<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Infrastructure;

use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Outbox\Application\MessagePublisher;
use App\Chapter11_OutboxPattern\Reporting\Application\Subscriber\OrderPlacedReadModelUpdater;

/**
 * Zástupce brokera. V knize relay posílá zprávu na event.bus do transportu
 * async_events a subscriber ji dostane od workeru. Ukázka broker nemá,
 * takže zprávu předá subscriberovi rovnou.
 *
 * Přes sdílenou sběrnici ji neposílá záměrně: OrderPlacedIntegrationEvent
 * odebírá i OrderProcessManager z ukázky ke kapitole o ságách a každé
 * spuštění relaye by pak rozjelo i ságu.
 */
final class InProcessPublisher implements MessagePublisher
{
    // Přepínač pro ukázku výpadku brokera: relay pak zprávu neodešle
    // a řádek zůstane pending s odkladem.
    private bool $unavailable = false;

    public function __construct(
        private readonly OrderPlacedReadModelUpdater $orderPlacedUpdater,
    ) {}

    public function simulateOutage(bool $unavailable = true): void
    {
        $this->unavailable = $unavailable;
    }

    public function publish(object $message): void
    {
        if ($this->unavailable) {
            throw new \RuntimeException('Broker je nedostupný (connection refused).');
        }

        match (true) {
            $message instanceof OrderPlacedIntegrationEvent => ($this->orderPlacedUpdater)($message),
            default => throw new \LogicException('Pro zprávu ' . $message::class . ' nemá ukázka odběratele.'),
        };
    }
}
