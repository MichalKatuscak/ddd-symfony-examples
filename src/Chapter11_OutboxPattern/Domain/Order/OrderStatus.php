<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Cancelled = 'cancelled';
}
