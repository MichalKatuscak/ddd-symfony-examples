<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Exception;

use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

/**
 * Chybějící objednávka je chyba volajícího, ne prázdný výsledek –
 * proto OrderRepository::get() hází výjimku místo vracení null.
 */
final class OrderNotFoundException extends \DomainException
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->value));
    }
}
