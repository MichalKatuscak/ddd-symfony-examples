<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject;

/**
 * Stavy tvoří uzavřený graf. Cesty, které v něm nejsou, nejsou „ještě
 * neimplementované“ – jsou zakázané.
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
