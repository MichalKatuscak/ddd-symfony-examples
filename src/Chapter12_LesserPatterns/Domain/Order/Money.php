<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Exception\CurrencyMismatchException;
use App\Shared\Domain\Currency;

final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency = Currency::CZK,
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amountInCents > $this->amountInCents) {
            throw CurrencyMismatchException::cannotSubtract();
        }

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amountInCents * $qty, $this->currency);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents >= $other->amountInCents;
    }

    public function formatted(): string
    {
        return number_format($this->amountInCents / 100, 2) . ' ' . $this->currency->value;
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
