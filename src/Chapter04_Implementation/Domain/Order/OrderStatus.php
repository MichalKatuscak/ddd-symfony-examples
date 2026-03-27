<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
