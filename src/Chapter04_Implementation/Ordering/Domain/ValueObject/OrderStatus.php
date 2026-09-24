<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Ordering\Domain\ValueObject;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * Vrátí stavy, do kterých je možné z aktuálního stavu přejít.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            // Odeslanou zásilku storno nevrátí; od tohoto bodu se situace
            // řeší kompenzací v sáze, ne přechodem agregátu.
            self::Shipped => [self::Delivered],
            self::Delivered => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
