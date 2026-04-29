<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Customer;

use Symfony\Component\Uid\Uuid;

final readonly class CustomerId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('CustomerId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }
}
