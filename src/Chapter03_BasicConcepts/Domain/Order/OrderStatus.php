<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
