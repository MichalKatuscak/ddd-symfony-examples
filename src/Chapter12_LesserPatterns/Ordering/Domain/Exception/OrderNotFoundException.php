<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Exception;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\OrderId;

final class OrderNotFoundException extends \DomainException
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->value));
    }
}
