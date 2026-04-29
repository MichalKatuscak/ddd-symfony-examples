<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Application\DispatchOutbox;

use App\Chapter11_OutboxPattern\Application\OnOrderPlaced\OrderPlacedReadModelUpdater;
use App\Chapter11_OutboxPattern\Domain\Order\OrderPlaced;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxMessage;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Polling relay — bere pending zprávy z outbox tabulky a "publikuje" je.
 *
 * V produkci by tady místo přímého volání subscriberů byl
 * Symfony Messenger transport (RabbitMQ / AMQP / doctrine queue).
 * Po úspěšném acku z brokeru se row označí jako sent.
 *
 * Pro účel ukázky relay rovnou zavolá in-memory subscriber. Pattern zůstává
 * stejný: "fetch pending → dispatch → mark sent".
 */
final readonly class OutboxRelay
{
    public function __construct(
        private OutboxRepositoryInterface $outbox,
        private OrderPlacedReadModelUpdater $orderPlacedUpdater,
    ) {
    }

    /**
     * @return array{processed: int, failed: int}
     */
    public function dispatchPending(int $batchSize = 100): array
    {
        $pending = $this->outbox->findPending($batchSize);

        $processed = 0;
        $failed = 0;

        foreach ($pending as $message) {
            try {
                $this->dispatchOne($message);
                $message->markSent();
                ++$processed;
            } catch (\Throwable) {
                $message->markFailed();
                ++$failed;
            }

            $this->outbox->save($message);
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    private function dispatchOne(OutboxMessage $message): void
    {
        match ($message->type) {
            OrderPlaced::class => ($this->orderPlacedUpdater)(
                $this->reconstructOrderPlaced($message),
            ),
            default => null, // jiné eventy v této ukázce nemáme
        };
    }

    private function reconstructOrderPlaced(OutboxMessage $message): OrderPlaced
    {
        $payload = $message->payload;

        return new OrderPlaced(
            eventId: (string) ($payload['eventId'] ?? Uuid::v4()->toRfc4122()),
            orderId: (string) ($payload['orderId'] ?? ''),
            customerId: (string) ($payload['customerId'] ?? ''),
            amount: (int) ($payload['amount'] ?? 0),
            occurredAt: $message->occurredAt,
        );
    }
}
