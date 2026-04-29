<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

final readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency = 'CZK',
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amount > $this->amount) {
            throw new \DomainException('Cannot subtract — would result in negative amount');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Currency mismatch: %s vs %s', $this->currency, $other->currency),
            );
        }
    }
}
