<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

use Symfony\Component\Messenger\Exception\TransportException;

/**
 * Jeden průchod relaye: fetch pending → publish → mark sent / mark failed.
 *
 * V knize tato smyčka žije přímo v OutboxDispatchCommand. Ukázka ji
 * vytahuje do služby, aby jeden průchod mohl spustit i controller.
 */
final readonly class OutboxRelay
{
    public function __construct(
        private OutboxRepository $outbox,
        private OutboxMessageFactory $factory,
        private MessagePublisher $publisher,
    ) {}

    /**
     * @return array{processed: int, failed: int, brokerUnavailable: bool}
     */
    public function dispatchPending(int $batchSize = 100): array
    {
        $processed = 0;
        $failed = 0;

        foreach ($this->outbox->fetchPending($batchSize) as $row) {
            try {
                // eventId pro deduplikaci v Inboxu cestuje v payloadu
                // (OrderPlacedIntegrationEvent::$eventId) – stamp není potřeba.
                $this->publisher->publish($this->factory->reconstitute($row));
                $this->outbox->markSent($row->id);
                ++$processed;
            } catch (TransportException) {
                // Broker je nedostupný, zpráva za to nemůže. Řádek zůstává
                // pending bez započteného pokusu a průchod se přeruší; čekání
                // s rostoucím backoffem řídí worker (Backpressure v 15.07).
                return ['processed' => $processed, 'failed' => $failed, 'brokerUnavailable' => true];
            } catch (\Throwable $e) {
                // Chyba konkrétní zprávy (neznámý typ, denormalizace): tu
                // markFailed() počítá do attempts a odkládá backoffem;
                // do failed řádek propadne až po pátém pokusu.
                $this->outbox->markFailed($row->id, $e->getMessage());
                ++$failed;
            }
        }

        return ['processed' => $processed, 'failed' => $failed, 'brokerUnavailable' => false];
    }
}
