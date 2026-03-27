<?php
declare(strict_types=1);
namespace App\Chapter01_WhatIsDDD\Domain\SharedKernel;

final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('ProductId cannot be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
