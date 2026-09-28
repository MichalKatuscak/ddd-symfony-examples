<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Exception;

use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartId;

final class CartNotFoundException extends \DomainException
{
    public static function withId(CartId $id): self
    {
        return new self(sprintf('Cart "%s" not found.', $id->value));
    }
}
