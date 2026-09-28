<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

use Symfony\Component\Messenger\Exception\TransportException;

/**
 * Místo, kde kniha volá $bus->dispatch($message, [new TransportNamesStamp(['async_events'])]).
 * Ukázka broker nemá, proto relay publikuje přes tento port.
 */
interface MessagePublisher
{
    /**
     * @throws TransportException když broker není dostupný (zpráva za to nemůže)
     * @throws \Throwable        když zprávu nejde doručit kvůli ní samé
     */
    public function publish(object $message): void;
}
