<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\Domain\ValueObject;

/** Kanonické stavy objednávky; event-sourced model jich používá část. */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
