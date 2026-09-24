<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\ValueObject;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
