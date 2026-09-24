<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

/**
 * Místo, kde kniha volá $bus->dispatch($message, [new TransportNamesStamp(['async_events'])]).
 * Ukázka broker nemá, proto relay publikuje přes tenhle port.
 */
interface MessagePublisher
{
    /** @throws \Throwable když broker zprávu nepřijme */
    public function publish(object $message): void;
}
