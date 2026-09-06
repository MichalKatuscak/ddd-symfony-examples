<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Domain;

use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxMessage;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxStatus;
use PHPUnit\Framework\TestCase;

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

        self::assertSame(OutboxStatus::Pending, $message->status());
        self::assertSame(1, $message->attempts());
        self::assertSame('broker unavailable', $message->lastError());
    }

    public function test_failed_message_waits_before_next_attempt(): void
    {
        $message = $this->message();
        $now = new \DateTimeImmutable();

        $message->markFailed('timeout');

        // Odklad je exponenciální, takže hned po selhání řádek na řadu nepřijde.
        self::assertFalse($message->isAvailableAt($now));
        self::assertTrue($message->isAvailableAt($now->modify('+10 seconds')));
    }

    public function test_message_gives_up_after_five_attempts(): void
    {
        $message = $this->message();

        for ($i = 0; $i < 5; ++$i) {
            $message->markFailed('still failing');
        }

        self::assertSame(OutboxStatus::Failed, $message->status());
        // Vzdaný řádek už relay nebere, i kdyby odklad uplynul.
        self::assertFalse($message->isAvailableAt(new \DateTimeImmutable('+1 year')));
    }

    public function test_successful_send_clears_last_error(): void
    {
        $message = $this->message();
        $sentAt = new \DateTimeImmutable('2026-09-06 10:00:00');

        $message->markFailed('first attempt failed');
        $message->markSent($sentAt);

        self::assertSame(OutboxStatus::Sent, $message->status());
        self::assertEquals($sentAt, $message->sentAt());
        self::assertNull($message->lastError());
    }

    private function message(): OutboxMessage
    {
        return new OutboxMessage(
            id: 'evt-1',
            type: 'OrderPlaced',
            payload: ['orderId' => 'ord-1'],
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
