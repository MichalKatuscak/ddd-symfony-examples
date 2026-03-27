<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final readonly class ProductId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('ProductId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
