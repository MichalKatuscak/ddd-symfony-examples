<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Outbox;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
