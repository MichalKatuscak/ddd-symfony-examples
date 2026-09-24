<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

/**
 * Jeden průchod relaye: fetch pending → publish → mark sent / mark failed.
 *
 * V knize tahle smyčka žije přímo v OutboxDispatchCommand. Ukázka ji
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
     * @return array{processed: int, failed: int}
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
            } catch (\Throwable $e) {
                // Výpadek brokera řádek neodepíše: markFailed() ho nechá
                // pending s odkladem a do failed pošle až po pátém pokusu.
                $this->outbox->markFailed($row->id, $e->getMessage());
                ++$failed;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }
}
