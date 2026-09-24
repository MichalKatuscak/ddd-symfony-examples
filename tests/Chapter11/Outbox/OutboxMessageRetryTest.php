<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Outbox;

use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Chování, na kterém stojí spolehlivost relaye: jeden neúspěch ještě není
 * konec, ale ani se nesmí opakovat okamžitě.
 */
final class OutboxMessageRetryTest extends TestCase
{
    public function test_first_failure_keeps_message_pending(): void
    {
        $message = $this->message();

        $message->markFailed('broker unavailable');

        self::assertSame('pending', $message->status);
        self::assertSame(1, $message->attempts);
        self::assertSame('broker unavailable', $message->lastError);
    }

    public function test_failed_message_waits_before_next_attempt(): void
    {
        $message = $this->message();
        $now = new \DateTimeImmutable();

        $message->markFailed('timeout');

        // Odklad je exponenciální (2^attempts sekund), hned po selhání řádek na řadu nepřijde.
        self::assertGreaterThan($now, $message->availableAt);
        self::assertLessThanOrEqual($now->modify('+3 seconds'), $message->availableAt);
    }

    public function test_message_gives_up_after_five_attempts(): void
    {
        $message = $this->message();

        for ($i = 0; $i < 4; ++$i) {
            $message->markFailed('still failing');
        }
        self::assertSame('pending', $message->status);

        $message->markFailed('still failing');
        self::assertSame('failed', $message->status);
        self::assertSame(5, $message->attempts);
    }

    public function test_successful_send_clears_last_error(): void
    {
        $message = $this->message();
        $sentAt = new \DateTimeImmutable('2026-09-24 10:00:00');

        $message->markFailed('first attempt failed');
        $message->markSent($sentAt);

        self::assertSame('sent', $message->status);
        self::assertSame($sentAt, $message->sentAt);
        self::assertNull($message->lastError);
    }

    public function test_row_has_the_eleven_columns_from_the_book(): void
    {
        $columns = array_map(
            static fn (\ReflectionProperty $p): string => $p->getName(),
            (new \ReflectionClass(OutboxMessage::class))->getProperties(),
        );

        self::assertSame([
            'id', 'messageType', 'aggregateType', 'aggregateId', 'payload', 'status',
            'occurredAt', 'attempts', 'availableAt', 'sentAt', 'lastError',
        ], $columns);
    }

    private function message(): OutboxMessage
    {
        return new OutboxMessage(
            id: Uuid::v7(),
            messageType: 'OrderPlacedIntegrationEvent',
            aggregateType: 'Order',
            aggregateId: (string) Uuid::v7(),
            payload: ['orderId' => 'ord-1'],
        );
    }
}
