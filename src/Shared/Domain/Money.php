<?php

declare(strict_types=1);

namespace App\Shared\Domain;

final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency = Currency::CZK,
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Částka nesmí být záporná.');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(sprintf(
                'Nelze sčítat %s a %s.',
                $this->currency->value,
                $other->currency->value,
            ));
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountInCents * $factor, $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' ' . $this->currency->value;
    }
}
