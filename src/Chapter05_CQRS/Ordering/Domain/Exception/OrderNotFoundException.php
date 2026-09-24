<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Exception;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;

final class OrderNotFoundException extends \DomainException
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Objednávka "%s" neexistuje.', $id->value));
    }
}
