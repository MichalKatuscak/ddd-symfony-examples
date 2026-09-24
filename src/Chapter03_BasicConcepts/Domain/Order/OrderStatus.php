<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

// Uzavřený výčet stavů bez další logiky: na to stačí enum,
// plný hodnotový objekt by nic nepřidal.
enum OrderStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Paid      = 'paid';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
