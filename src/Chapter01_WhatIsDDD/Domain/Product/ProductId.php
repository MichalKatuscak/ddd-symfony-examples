<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

use Symfony\Component\Uid\Uuid;

final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('ProductId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }
}
