<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final readonly class Price
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add prices in different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
