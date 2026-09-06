<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\Currency;

final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {}

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Cannot add %s to %s', $other->currency->value, $this->currency->value)
            );
        }
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amountInCents * $qty, $this->currency);
    }

    public function percentage(int $pct): self
    {
        return new self((int) round($this->amountInCents * $pct / 100), $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amountInCents / 100, 2) . ' ' . $this->currency->value;
    }
}
