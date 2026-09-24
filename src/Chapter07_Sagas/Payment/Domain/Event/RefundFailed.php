<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Domain\Event;

use Symfony\Component\Uid\Uuid;

final readonly class RefundFailed
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $failureReason,
    ) {}
}
